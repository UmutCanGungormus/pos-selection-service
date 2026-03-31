<?php

use App\Contracts\PosRateProviderInterface;
use App\Jobs\SyncPosRatesJob;
use App\Models\PosRate;
use Illuminate\Support\Facades\Queue;

it('dispatches sync job via endpoint', function () {
    Queue::fake();

    $response = $this->postJson('/api/pos/sync');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('pos.sync_dispatched'));

    Queue::assertPushed(SyncPosRatesJob::class);
});

it('syncs POS rates when job is processed', function () {
    $mockProvider = Mockery::mock(PosRateProviderInterface::class);
    $mockProvider->shouldReceive('fetchRates')->once()->andReturn(collect([
        [
            'pos_name' => 'TestBank',
            'card_type' => 'credit',
            'card_brand' => 'testcard',
            'installment' => 3,
            'currency' => 'TRY',
            'commission_rate' => 0.025,
            'min_fee' => 0,
            'priority' => 5,
        ],
    ]));

    $this->app->instance(PosRateProviderInterface::class, $mockProvider);

    (new SyncPosRatesJob)->handle($this->app->make(\App\Services\PosRateSyncService::class));

    $this->assertDatabaseHas('pos_rates', [
        'pos_name' => 'TestBank',
        'card_type' => 'credit',
        'card_brand' => 'testcard',
        'commission_rate' => 0.025,
    ]);
});

it('updates existing rates instead of duplicating', function () {
    PosRate::create([
        'pos_name' => 'TestBank',
        'card_type' => 'credit',
        'card_brand' => 'testcard',
        'installment' => 3,
        'currency' => 'TRY',
        'commission_rate' => 0.025,
        'min_fee' => 0,
        'priority' => 5,
    ]);

    $mockProvider = Mockery::mock(PosRateProviderInterface::class);
    $mockProvider->shouldReceive('fetchRates')->once()->andReturn(collect([
        [
            'pos_name' => 'TestBank',
            'card_type' => 'credit',
            'card_brand' => 'testcard',
            'installment' => 3,
            'currency' => 'TRY',
            'commission_rate' => 0.030,
            'min_fee' => 0,
            'priority' => 5,
        ],
    ]));

    $this->app->instance(PosRateProviderInterface::class, $mockProvider);

    (new SyncPosRatesJob)->handle($this->app->make(\App\Services\PosRateSyncService::class));

    expect(PosRate::count())->toBe(1)
        ->and((float) PosRate::first()->commission_rate)->toBe(0.03);
});
