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

        // In-memory sort is intentional here. The repository's findLowestCostRate()
        // sorts only by commission_rate at DB level, which does not account for min_fee.
        // The actual cost is max(amount * commission_rate, min_fee), which depends on
        // the request amount -- a runtime value that cannot be evaluated in SQL without
        // a raw expression. For typical POS rate cardinality (<100 rows per filter
        // combination) the in-memory sort is negligible and guarantees correctness.
        $bestRate = $posRates
            ->sortBy([
                fn (PosRate $a, PosRate $b) => $a->calculateCost($criteria->amount) <=> $b->calculateCost($criteria->amount),
                fn (PosRate $a, PosRate $b) => $b->priority <=> $a->priority,
            ])
            ->first();

        return new PosSelectionOutcome(
            filters: $this->buildFilters($criteria),
            bestRate: $bestRate,
            cost: $bestRate->calculateCost($criteria->amount),
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
