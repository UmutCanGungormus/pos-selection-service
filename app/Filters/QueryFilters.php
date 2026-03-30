<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilters
{
    protected Builder $builder;

    public function __construct(
        protected readonly Request $request,
    ) {}

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters() as $filter => $value) {
            $method = str($filter)->camel()->toString();

            if (method_exists($this, $method) && $this->isApplicable($value)) {
                $this->{$method}($value);
            }
        }

        return $this->builder;
    }

    protected function filters(): array
    {
        return $this->request->only($this->allowedFilters());
    }

    /** @return list<string> */
    abstract protected function allowedFilters(): array;

    protected function isApplicable(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
