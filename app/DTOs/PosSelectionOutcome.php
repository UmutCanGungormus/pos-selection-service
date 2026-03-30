<?php

namespace App\DTOs;

use App\Models\PosRate;

final readonly class PosSelectionOutcome
{
    public function __construct(
        public array $filters,
        public PosRate $bestRate,
        public float $cost,
    ) {}

    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'cost' => round($this->cost, 2),
            'overall_min' => [
                'pos_name' => $this->bestRate->pos_name,
                'card_type' => $this->bestRate->card_type->value,
                'card_brand' => $this->bestRate->card_brand,
                'installment' => $this->bestRate->installment,
                'currency' => $this->bestRate->currency->value,
                'commission_rate' => (float) $this->bestRate->commission_rate,
            ],
        ];
    }
}
