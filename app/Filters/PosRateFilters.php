<?php

namespace App\Filters;

use App\Enums\CardType;
use App\Enums\Currency;

class PosRateFilters extends QueryFilters
{
    protected function allowedFilters(): array
    {
        return ['card_type', 'card_brand', 'installment', 'currency'];
    }

    public function cardType(string $value): void
    {
        $this->builder->where('card_type', CardType::from($value));
    }

    public function cardBrand(string $value): void
    {
        $this->builder->where('card_brand', $value);
    }

    public function installment(int|string $value): void
    {
        $this->builder->where('installment', (int) $value);
    }

    public function currency(string $value): void
    {
        $this->builder->where('currency', Currency::from($value));
    }
}
