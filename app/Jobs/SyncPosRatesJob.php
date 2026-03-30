<?php

namespace App\Jobs;

use App\Jobs\Middleware\RateLimitedSync;
use App\Services\PosRateSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncPosRatesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct()
    {
        $this->onQueue('sync');
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new RateLimitedSync];
    }

    public function handle(PosRateSyncService $syncService): void
    {
        Log::info('SyncPosRatesJob started.');

        $count = $syncService->sync();

        Log::info('SyncPosRatesJob completed.', ['synced' => $count]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('SyncPosRatesJob failed.', [
            'message' => $exception?->getMessage(),
        ]);
    }
}
