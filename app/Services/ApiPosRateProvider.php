<?php

namespace App\Services;

use App\Contracts\PosRateProviderInterface;
use App\Exceptions\PosRateApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

readonly class ApiPosRateProvider implements PosRateProviderInterface
{
    public function __construct(
        private string $apiUrl,
    ) {}

    public function fetchRates(): Collection
    {
        try {
            $response = Http::posRateApi()->get($this->apiUrl);

            if ($response->failed()) {
                throw new PosRateApiException(
                    "API returned HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            if (! is_array($data)) {
                throw new PosRateApiException('Invalid response format: expected an array.');
            }

            Log::info('POS rates fetched successfully.', ['count' => count($data)]);

            return collect($data);
        } catch (PosRateApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new PosRateApiException($e->getMessage(), $e);
        }
    }
}
