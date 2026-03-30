<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

final class RateLimitedSync
{
    public function handle(object $job, callable $next): void
    {
        Redis::throttle('sync-pos-rates')
            ->block(0)
            ->allow(1)
            ->every(300)
            ->then(
                fn () => $next($job),
                fn () => $job->release(300),
            );
    }
}
