<?php

namespace App\Services;

use App\Contracts\PosRateProviderInterface;
use App\Repositories\Contracts\PosRateRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosRateSyncService
{
    public function __construct(
        private readonly PosRateProviderInterface $provider,
        private readonly PosRateRepositoryInterface $repository,
    ) {}

    public function sync(): int
    {
        $rates = $this->provider->fetchRates();

        $synced = 0;

        DB::transaction(function () use ($rates, &$synced): void {
            foreach ($rates as $rateData) {
                $this->repository->upsertRate(
                    attributes: [
                        'pos_name' => $rateData['pos_name'],
                        'card_type' => $rateData['card_type'],
                        'card_brand' => $rateData['card_brand'],
                        'installment' => $rateData['installment'],
                        'currency' => $rateData['currency'],
                    ],
                    values: [
                        'commission_rate' => $rateData['commission_rate'],
                        'min_fee' => $rateData['min_fee'] ?? 0,
                        'priority' => $rateData['priority'] ?? 0,
                    ],
                );

                $synced++;
            }
        });

        Log::info('POS rates synced.', ['count' => $synced]);

        return $synced;
    }
}
