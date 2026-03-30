<?php

namespace App\Http\Controllers;

use App\Jobs\SyncPosRatesJob;
use App\Services\PosRateSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

class PosRateSyncController
{
    public function sync(PosRateSyncService $syncService): JsonResponse
    {
        $count = $syncService->sync();

        return Response::success(
            data: ['synced_count' => $count],
            message: __('pos.sync_success'),
        );
    }

    public function dispatch(): JsonResponse
    {
        SyncPosRatesJob::dispatch()->onQueue('sync');

        return Response::success(
            message: __('pos.sync_dispatched'),
        );
    }
}
