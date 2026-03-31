<?php

namespace App\Query\Paginate;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryPaginate
{
    private const MAX_PER_PAGE = 250;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): LengthAwarePaginator
    {
        return $builder->paginate($this->perPage());
    }

    public function page(): int
    {
        return (int) $this->request->get('page', 1);
    }

    public function perPage(): int
    {
        $perPage = (int) ($this->request->get('per_page') ?: self::MAX_PER_PAGE);

        return min($perPage, self::MAX_PER_PAGE);
    }
}
