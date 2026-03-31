<?php

namespace App\Traits\Query;

use App\Query\Paginate\QueryPaginate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

trait Paginatable
{
    public function scopeCustomPaginate(Builder $query, QueryPaginate $paginate): LengthAwarePaginator
    {
        return $paginate->apply($query);
    }
}