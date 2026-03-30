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
        $enum = CardType::tryFrom($value);

        if ($enum === null) {
            abort(422, __('pos.invalid_card_type'));
        }

        $this->builder->where('card_type', $enum);
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
        $enum = Currency::tryFrom($value);

        if ($enum === null) {
            abort(422, __('pos.invalid_currency'));
        }

        $this->builder->where('currency', $enum);
    }
}
