<?php

use App\Contracts\PosRateProviderInterface;
use App\Models\PosRate;
use App\Repositories\Contracts\PosRateRepositoryInterface;
use App\Services\PosRateSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->provider = Mockery::mock(PosRateProviderInterface::class);
    $this->repository = Mockery::mock(PosRateRepositoryInterface::class);
    $this->service = new PosRateSyncService($this->provider, $this->repository);
});

it('fetches rates from provider and upserts each one', function () {
    $rates = collect([
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.027,
            'min_fee' => 0,
            'priority' => 6,
        ],
        [
            'pos_name' => 'Akbank',
            'card_type' => 'credit',
            'card_brand' => 'axess',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.028,
            'min_fee' => 0,
            'priority' => 5,
        ],
    ]);

    $this->provider->shouldReceive('fetchRates')->once()->andReturn($rates);
    $this->repository->shouldReceive('upsertRate')->twice()->andReturn(new PosRate);

    Log::shouldReceive('info')->once()->with('POS rates synced.', ['count' => 2]);

    $count = $this->service->sync();

    expect($count)->toBe(2);
});

it('passes correct attributes and values to upsertRate', function () {
    $rates = collect([
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.027,
            'min_fee' => 1.50,
            'priority' => 6,
        ],
    ]);

    $this->provider->shouldReceive('fetchRates')->once()->andReturn($rates);

    $this->repository->shouldReceive('upsertRate')->once()->with(
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
        ],
        [
            'commission_rate' => 0.027,
            'min_fee' => 1.50,
            'priority' => 6,
        ],
    )->andReturn(new PosRate);

    Log::shouldReceive('info')->once();

    $this->service->sync();
});

it('defaults min_fee and priority when not provided', function () {
    $rates = collect([
        [
            'pos_name' => 'Pos1',
            'card_type' => 'credit',
            'card_brand' => 'brand1',
            'installment' => 1,
            'currency' => 'TRY',
            'commission_rate' => 0.01,
        ],
    ]);

    $this->provider->shouldReceive('fetchRates')->once()->andReturn($rates);

    $this->repository->shouldReceive('upsertRate')->once()->with(
        Mockery::type('array'),
        Mockery::on(fn (array $values) => $values['min_fee'] === 0 && $values['priority'] === 0),
    )->andReturn(new PosRate);

    Log::shouldReceive('info')->once();

    $this->service->sync();
});

it('returns zero when provider returns empty collection', function () {
    $this->provider->shouldReceive('fetchRates')->once()->andReturn(collect());
    $this->repository->shouldNotReceive('upsertRate');

    Log::shouldReceive('info')->once()->with('POS rates synced.', ['count' => 0]);

    $count = $this->service->sync();

    expect($count)->toBe(0);
});

it('wraps upserts in a database transaction', function () {
    $this->provider->shouldReceive('fetchRates')->once()->andReturn(collect([
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.027,
        ],
    ]));

    $this->repository->shouldReceive('upsertRate')->once()->andReturn(new PosRate);

    DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $cb) => $cb());

    Log::shouldReceive('info')->once();

    $this->service->sync();
});
