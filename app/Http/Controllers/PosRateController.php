<?php

namespace App\Http\Controllers;

use App\Filters\PosRateFilters;
use App\Http\Resources\PosRateResource;
use App\Query\Paginate\QueryPaginate;
use App\Services\PosRateService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PosRateController
{
    public function __construct(
        private readonly PosRateService $service,
    ) {}

    public function index(PosRateFilters $filters, QueryPaginate $paginate): AnonymousResourceCollection
    {
        return PosRateResource::collection(
            $this->service->list($filters, $paginate),
        );
    }
}