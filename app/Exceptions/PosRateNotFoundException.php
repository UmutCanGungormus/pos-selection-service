<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PosRateNotFoundException extends HttpException
{
    public function __construct(array $filters = [])
    {
        if (empty($filters)) {
            $message = __('pos.rate_not_found');
        } else {
            $filterString = collect($filters)
                ->filter()
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode(', ');

            $message = __('pos.rate_not_found_with_filters', ['filters' => $filterString]);
        }

        parent::__construct(404, $message);
    }
}
