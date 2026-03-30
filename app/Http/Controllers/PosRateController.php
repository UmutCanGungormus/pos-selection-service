<?php

namespace App\Http\Controllers;

use App\Filters\PosRateFilters;
use App\Http\Resources\PosRateResource;
use App\Repositories\Contracts\PosRateRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PosRateController
{
    public function __construct(
        private readonly PosRateRepositoryInterface $repository,
    ) {}

    public function index(Request $request, PosRateFilters $filters): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);

        $rates = $this->repository->paginateWithFilters($filters, $perPage);

        return PosRateResource::collection($rates);
    }
}
