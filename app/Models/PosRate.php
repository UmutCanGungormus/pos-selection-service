<?php

namespace App\Models;

use App\Enums\CardType;
use App\Enums\Currency;
use App\Traits\Query\Filterable;
use App\Traits\Query\Paginatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PosRate extends Model
{
    use Filterable, Paginatable;

    protected $fillable = [
        'pos_name',
        'card_type',
        'card_brand',
        'installment',
        'currency',
        'commission_rate',
        'min_fee',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'card_type' => CardType::class,
            'currency' => Currency::class,
            'installment' => 'integer',
            'commission_rate' => 'decimal:4',
            'min_fee' => 'decimal:2',
            'priority' => 'integer',
        ];
    }

    public function scopeForCardType(Builder $query, CardType $cardType): Builder
    {
        return $query->where('card_type', $cardType);
    }

    public function scopeForCardBrand(Builder $query, string $cardBrand): Builder
    {
        return $query->where('card_brand', $cardBrand);
    }

    public function scopeForInstallment(Builder $query, int $installment): Builder
    {
        return $query->where('installment', $installment);
    }

    public function scopeForCurrency(Builder $query, Currency $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    public function calculateCost(float $amount): float
    {
        $commissionCost = $amount * (float) $this->commission_rate;

        return max($commissionCost, (float) $this->min_fee);
    }
}
