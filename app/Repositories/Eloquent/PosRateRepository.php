<?php

namespace App\Repositories\Eloquent;

use App\Enums\CardType;
use App\Enums\Currency;
use App\Filters\PosRateFilters;
use App\Models\PosRate;
use App\Repositories\AbstractEloquentRepository;
use App\Repositories\Contracts\PosRateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends AbstractEloquentRepository<PosRate>
 */
class PosRateRepository extends AbstractEloquentRepository implements PosRateRepositoryInterface
{
    public function __construct(PosRate $model)
    {
        parent::__construct($model);
    }

    public function findMatchingRates(
        CardType $cardType,
        int $installment,
        Currency $currency,
        ?string $cardBrand = null,
    ): Collection {
        return $this->model->newQuery()
            ->forCardType($cardType)
            ->forInstallment($installment)
            ->forCurrency($currency)
            ->when($cardBrand, fn ($query, $brand) => $query->forCardBrand($brand))
            ->get();
    }

    public function paginateWithFilters(PosRateFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->filter($filters)
            ->orderBy('pos_name')
            ->orderBy('installment')
            ->paginate($perPage);
    }

    public function upsertRate(array $attributes, array $values): PosRate
    {
        return $this->model->newQuery()->updateOrCreate($attributes, $values);
    }

    public function findLowestCostRate(
        CardType $cardType,
        int $installment,
        Currency $currency,
        ?string $cardBrand = null,
    ): ?PosRate {
        return $this->model->newQuery()
            ->forCardType($cardType)
            ->forInstallment($installment)
            ->forCurrency($currency)
            ->when($cardBrand, fn ($query, $brand) => $query->forCardBrand($brand))
            ->orderBy('commission_rate')
            ->orderByDesc('priority')
            ->first();
    }
}
