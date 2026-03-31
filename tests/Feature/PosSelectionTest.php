<?php

use App\Models\PosRate;

beforeEach(function () {
    PosRate::insert([
        [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.027,
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
            'currency' => 'TRY',
            'commission_rate' => 0.028,
            'min_fee' => 0,
            'priority' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'pos_name' => 'Denizbank',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 3,
            'currency' => 'USD',
            'commission_rate' => 0.031,
            'min_fee' => 0,
            'priority' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

it('returns the lowest cost POS for given criteria', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 1000,
        'installment' => 6,
        'currency' => 'TRY',
        'card_type' => 'credit',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.overall_min.pos_name', 'Garanti')
        ->assertJsonPath('data.overall_min.commission_rate', 0.027)
        ->assertJsonPath('data.price', 27)
        ->assertJsonPath('data.payable_total', 1027)
        ->assertJsonPath('data.filters.installment', 6)
        ->assertJsonPath('data.filters.currency', 'TRY')
        ->assertJsonPath('data.filters.card_type', 'credit')
        ->assertJsonPath('data.filters.card_brand', null);
});

it('filters by card_brand when provided', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 1000,
        'installment' => 3,
        'currency' => 'USD',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.overall_min.pos_name', 'Denizbank')
        ->assertJsonPath('data.filters.card_brand', 'bonus');
});

it('returns 404 when no POS matches the criteria', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 1000,
        'installment' => 24,
        'currency' => 'TRY',
        'card_type' => 'debit',
    ]);

    $response->assertNotFound()
        ->assertJsonPath('success', false);
});

it('validates required fields', function () {
    $response = $this->postJson('/api/pos/select', []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('validates currency is one of TRY, USD, EUR', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 1000,
        'installment' => 3,
        'currency' => 'GBP',
        'card_type' => 'credit',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('validates card_type is one of credit, debit, unknown', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 1000,
        'installment' => 3,
        'currency' => 'TRY',
        'card_type' => 'prepaid',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('validates amount must be positive', function () {
    $response = $this->postJson('/api/pos/select', [
        'amount' => 0,
        'installment' => 3,
        'currency' => 'TRY',
        'card_type' => 'credit',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});
