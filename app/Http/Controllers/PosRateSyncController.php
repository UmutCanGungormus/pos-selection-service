<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPosRatesJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class PosRateSyncController
{
    public function __invoke(): JsonResponse
    {
        SyncPosRatesJob::dispatch();

        return Response::success(
            message: __('pos.sync_dispatched'),
        );
    }
}