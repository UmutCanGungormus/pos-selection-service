<?php

namespace App\Repositories\Contracts;

use App\Enums\CardType;
use App\Enums\Currency;
use App\Filters\PosRateFilters;
use App\Models\PosRate;
use App\Query\Paginate\QueryPaginate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepositoryInterface<PosRate>
 */
interface PosRateRepositoryInterface extends EloquentRepositoryInterface
{
    /**
     * @return Collection<int, PosRate>
     */
    public function findMatchingRates(
        CardType $cardType,
        int $installment,
        Currency $currency,
        ?string $cardBrand = null,
    ): Collection;

    /**
     * @param  array<string, mixed>  $attributes  Unique key columns
     * @param  array<string, mixed>  $values  Columns to update/set
     */
    public function upsertRate(array $attributes, array $values): PosRate;

    /**
     * @return LengthAwarePaginator<PosRate>
     */
    public function paginateWithFilters(PosRateFilters $filters, QueryPaginate $paginate): LengthAwarePaginator;

    public function findLowestCostRate(
        CardType $cardType,
        int $installment,
        Currency $currency,
        ?string $cardBrand = null,
    ): ?PosRate;
}
