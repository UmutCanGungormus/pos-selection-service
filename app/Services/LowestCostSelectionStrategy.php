<?php

namespace App\Services;

use App\Contracts\PosSelectionStrategyInterface;
use App\DTOs\PosSelectionCriteria;
use App\DTOs\PosSelectionOutcome;
use App\Exceptions\PosRateNotFoundException;
use App\Models\PosRate;
use App\Repositories\Contracts\PosRateRepositoryInterface;

class LowestCostSelectionStrategy implements PosSelectionStrategyInterface
{
    public function __construct(
        private readonly PosRateRepositoryInterface $repository,
    ) {}

    public function select(PosSelectionCriteria $criteria): PosSelectionOutcome
    {
        $posRates = $this->repository->findMatchingRates(
            cardType: $criteria->cardType,
            installment: $criteria->installment,
            currency: $criteria->currency,
            cardBrand: $criteria->cardBrand,
        );

        if ($posRates->isEmpty()) {
            throw new PosRateNotFoundException($this->buildFilters($criteria));
        }

        $bestRate = $posRates
            ->sortBy([
                fn (PosRate $a, PosRate $b) => $a->calculateCost($criteria->amount) <=> $b->calculateCost($criteria->amount),
                fn (PosRate $a, PosRate $b) => $b->priority <=> $a->priority,
            ])
            ->first();

        $price = $bestRate->calculateCost($criteria->amount);

        return new PosSelectionOutcome(
            filters: $this->buildFilters($criteria),
            bestRate: $bestRate,
            price: $price,
            payableTotal: $criteria->amount + $price,
        );
    }

    private function buildFilters(PosSelectionCriteria $criteria): array
    {
        return [
            'installment' => $criteria->installment,
            'currency' => $criteria->currency->value,
            'card_type' => $criteria->cardType->value,
            'card_brand' => $criteria->cardBrand,
        ];
    }
}
