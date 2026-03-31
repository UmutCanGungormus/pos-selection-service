<?php

namespace App\Services;

use App\Filters\PosRateFilters;
use App\Query\Paginate\QueryPaginate;
use App\Repositories\Contracts\PosRateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PosRateService
{
    public function __construct(
        private readonly PosRateRepositoryInterface $repository,
    ) {}

    public function list(PosRateFilters $filters, QueryPaginate $paginate): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $paginate);
    }
}
