<?php

use App\Jobs\Middleware\RateLimitedSync;
use Illuminate\Support\Facades\Redis;

it('calls next when rate limit is not exceeded', function () {
    $middleware = new RateLimitedSync;

    $job = new class
    {
        public bool $released = false;

        public function release(int $seconds): void
        {
            $this->released = true;
        }
    };

    $called = false;

    Redis::shouldReceive('throttle')
        ->with('sync-pos-rates')
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('block')
        ->with(0)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('allow')
        ->with(1)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('every')
        ->with(300)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('then')
        ->once()
        ->andReturnUsing(function (Closure $allow, Closure $reject) {
            $allow();
        });

    $middleware->handle($job, function ($passedJob) use (&$called, $job) {
        $called = true;
        expect($passedJob)->toBe($job);
    });

    expect($called)->toBeTrue()
        ->and($job->released)->toBeFalse();
});

it('releases job with 300 second delay when rate limited', function () {
    $middleware = new RateLimitedSync;

    $job = new class
    {
        public bool $released = false;

        public int $releaseDelay = 0;

        public function release(int $seconds): void
        {
            $this->released = true;
            $this->releaseDelay = $seconds;
        }
    };

    $called = false;

    Redis::shouldReceive('throttle')
        ->with('sync-pos-rates')
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('block')
        ->with(0)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('allow')
        ->with(1)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('every')
        ->with(300)
        ->once()
        ->andReturnSelf();
    Redis::shouldReceive('then')
        ->once()
        ->andReturnUsing(function (Closure $allow, Closure $reject) {
            $reject();
        });

    $middleware->handle($job, function () use (&$called) {
        $called = true;
    });

    expect($called)->toBeFalse()
        ->and($job->released)->toBeTrue()
        ->and($job->releaseDelay)->toBe(300);
});
