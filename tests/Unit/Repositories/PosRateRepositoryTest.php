<?php

use App\Enums\CardType;
use App\Enums\Currency;
use App\Models\PosRate;
use App\Repositories\Eloquent\PosRateRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new PosRateRepository(new PosRate);
});

// --- findMatchingRates ---

it('finds matching rates filtered by card_type, installment and currency', function () {
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
            'card_type' => 'debit',
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
            'pos_name' => 'YapiKredi',
            'card_type' => 'credit',
            'card_brand' => 'world',
            'installment' => 3,
            'currency' => 'TRY',
            'commission_rate' => 0.029,
            'min_fee' => 0,
            'priority' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'pos_name' => 'Isbank',
            'card_type' => 'credit',
            'card_brand' => 'maximum',
            'installment' => 6,
            'currency' => 'USD',
            'commission_rate' => 0.030,
            'min_fee' => 0,
            'priority' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $rates = $this->repository->findMatchingRates(
        CardType::Credit,
        6,
        Currency::TRY,
    );

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->pos_name)->toBe('Garanti');
});

it('filters by optional card_brand when provided', function () {
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
    ]);

    $rates = $this->repository->findMatchingRates(
        CardType::Credit,
        6,
        Currency::TRY,
        'axess',
    );

    expect($rates)->toHaveCount(1)
        ->and($rates->first()->pos_name)->toBe('Akbank');
});

it('returns empty collection when no rates match', function () {
    $rates = $this->repository->findMatchingRates(
        CardType::Debit,
        12,
        Currency::EUR,
    );

    expect($rates)->toBeEmpty();
});

// --- upsertRate ---

it('creates a new rate via upsertRate', function () {
    $rate = $this->repository->upsertRate(
        attributes: [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
        ],
        values: [
            'commission_rate' => 0.027,
            'min_fee' => 0,
            'priority' => 6,
        ],
    );

    expect($rate)->toBeInstanceOf(PosRate::class)
        ->and($rate->exists)->toBeTrue()
        ->and($rate->pos_name)->toBe('Garanti')
        ->and((float) $rate->commission_rate)->toBe(0.027);
});

it('updates an existing rate via upsertRate', function () {
    PosRate::create([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
        'priority' => 6,
    ]);

    $rate = $this->repository->upsertRate(
        attributes: [
            'pos_name' => 'Garanti',
            'card_type' => 'credit',
            'card_brand' => 'bonus',
            'installment' => 6,
            'currency' => 'TRY',
        ],
        values: [
            'commission_rate' => 0.035,
            'min_fee' => 1.00,
            'priority' => 10,
        ],
    );

    expect(PosRate::count())->toBe(1)
        ->and((float) $rate->commission_rate)->toBe(0.035)
        ->and((float) $rate->min_fee)->toBe(1.00)
        ->and($rate->priority)->toBe(10);
});

// --- findLowestCostRate ---

it('returns the rate with the lowest commission', function () {
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
            'commission_rate' => 0.020,
            'min_fee' => 0,
            'priority' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $rate = $this->repository->findLowestCostRate(
        CardType::Credit,
        6,
        Currency::TRY,
    );

    expect($rate)->not->toBeNull()
        ->and($rate->pos_name)->toBe('Akbank')
        ->and((float) $rate->commission_rate)->toBe(0.020);
});

it('returns null from findLowestCostRate when no rates match', function () {
    $rate = $this->repository->findLowestCostRate(
        CardType::Debit,
        12,
        Currency::EUR,
    );

    expect($rate)->toBeNull();
});

it('returns higher priority rate when commission rates are equal', function () {
    PosRate::insert([
        [
            'pos_name' => 'LowPriority',
            'card_type' => 'credit',
            'card_brand' => 'brand1',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.025,
            'min_fee' => 0,
            'priority' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'pos_name' => 'HighPriority',
            'card_type' => 'credit',
            'card_brand' => 'brand2',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.025,
            'min_fee' => 0,
            'priority' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $rate = $this->repository->findLowestCostRate(
        CardType::Credit,
        6,
        Currency::TRY,
    );

    expect($rate->pos_name)->toBe('HighPriority');
});

// --- Standard CRUD via base repository ---

it('finds a rate by id', function () {
    $created = PosRate::create([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
        'priority' => 6,
    ]);

    $found = $this->repository->findById($created->id);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($created->id)
        ->and($found->pos_name)->toBe('Garanti');
});

it('returns null when finding by non-existent id', function () {
    $found = $this->repository->findById(99999);

    expect($found)->toBeNull();
});

it('creates a rate via repository', function () {
    $rate = $this->repository->create([
        'pos_name' => 'NewPOS',
        'card_type' => 'credit',
        'card_brand' => 'newbrand',
        'installment' => 3,
        'currency' => 'USD',
        'commission_rate' => 0.015,
        'min_fee' => 0.50,
        'priority' => 1,
    ]);

    expect($rate)->toBeInstanceOf(PosRate::class)
        ->and($rate->exists)->toBeTrue()
        ->and($rate->pos_name)->toBe('NewPOS');
});

it('updates a rate via repository', function () {
    $created = PosRate::create([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
        'priority' => 6,
    ]);

    $updated = $this->repository->update($created->id, [
        'commission_rate' => 0.050,
    ]);

    expect((float) $updated->commission_rate)->toBe(0.050)
        ->and($updated->pos_name)->toBe('Garanti');
});

it('deletes a rate via repository', function () {
    $created = PosRate::create([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
        'priority' => 6,
    ]);

    $result = $this->repository->delete($created->id);

    expect($result)->toBeTrue()
        ->and(PosRate::find($created->id))->toBeNull();
});

it('checks if a rate exists via repository', function () {
    PosRate::create([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
        'priority' => 6,
    ]);

    expect($this->repository->exists(['pos_name' => 'Garanti']))->toBeTrue()
        ->and($this->repository->exists(['pos_name' => 'NonExistent']))->toBeFalse();
});

it('paginates rates via repository', function () {
    for ($i = 0; $i < 20; $i++) {
        PosRate::create([
            'pos_name' => "POS_{$i}",
            'card_type' => 'credit',
            'card_brand' => 'brand',
            'installment' => 1,
            'currency' => 'TRY',
            'commission_rate' => 0.01,
            'min_fee' => 0,
            'priority' => 0,
        ]);
    }

    $paginator = $this->repository->paginate(5);

    expect($paginator->count())->toBe(5)
        ->and($paginator->total())->toBe(20)
        ->and($paginator->lastPage())->toBe(4);
});
