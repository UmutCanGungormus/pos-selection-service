<?php

use App\Contracts\PosRateProviderInterface;
use App\Models\PosRate;

it('syncs POS rates from external API via endpoint', function () {
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

    $response = $this->postJson('/api/pos/sync');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.synced_count', 1);

    $this->assertDatabaseHas('pos_rates', [
        'pos_name' => 'TestBank',
        'card_type' => 'credit',
        'card_brand' => 'testcard',
        'commission_rate' => 0.025,
    ]);
});

it('dispatches sync job via endpoint', function () {
    $response = $this->postJson('/api/pos/sync/dispatch');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', __('pos.sync_dispatched'));
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

    $this->postJson('/api/pos/sync');

    expect(PosRate::count())->toBe(1)
        ->and((float) PosRate::first()->commission_rate)->toBe(0.03);
});
