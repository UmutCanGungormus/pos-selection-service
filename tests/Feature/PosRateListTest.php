<?php

use App\Models\PosRate;

beforeEach(function () {
    PosRate::insert([
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 3,
            'currency' => 'TRY',
            'commission_rate' => 0.026,
            'min_fee' => 0,
            'priority' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'pos_name' => 'Akbank',
            'card_type' => 'credit',
            'card_brand' => 'axess',
            'installment' => 6,
            'currency' => 'USD',
            'commission_rate' => 0.031,
            'min_fee' => 0,
            'priority' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

it('lists all POS rates with pagination', function () {
    $response = $this->getJson('/api/pos/rates');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('filters rates by card_type', function () {
    $response = $this->getJson('/api/pos/rates?card_type=credit');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters rates by currency', function () {
    $response = $this->getJson('/api/pos/rates?currency=USD');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.pos_name', 'Akbank');
});

it('filters rates by card_brand', function () {
    $response = $this->getJson('/api/pos/rates?card_brand=bonus');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.pos_name', 'Garanti');
});

it('returns POS rate resource structure', function () {
    $response = $this->getJson('/api/pos/rates');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'pos_name',
                    'card_type',
                    'card_brand',
                    'installment',
                    'currency',
                    'commission_rate',
                    'min_fee',
                    'priority',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

it('respects per_page parameter', function () {
    $response = $this->getJson('/api/pos/rates?per_page=1');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.last_page', 2);
});

it('returns 422 for invalid card_type enum value', function () {
    $response = $this->getJson('/api/pos/rates?card_type=invalid');

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('returns 422 for invalid currency enum value', function () {
    $response = $this->getJson('/api/pos/rates?currency=GBP');

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});
