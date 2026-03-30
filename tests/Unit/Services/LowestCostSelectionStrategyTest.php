<?php

use App\DTOs\PosSelectionCriteria;
use App\Enums\CardType;
use App\Enums\Currency;
use App\Exceptions\PosRateNotFoundException;
use App\Models\PosRate;
use App\Repositories\Eloquent\PosRateRepository;
use App\Services\LowestCostSelectionStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

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
            'pos_name' => 'YapiKredi',
            'card_type' => 'credit',
            'card_brand' => 'world',
            'installment' => 6,
            'currency' => 'TRY',
            'commission_rate' => 0.028,
            'min_fee' => 0,
            'priority' => 7,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
});

it('selects the POS with the lowest commission rate', function () {
    $strategy = new LowestCostSelectionStrategy(new PosRateRepository(new PosRate));

    $request = new PosSelectionCriteria(
        amount: 1000,
        installment: 6,
        currency: Currency::TRY,
        cardType: CardType::Credit,
    );

    $result = $strategy->select($request);

    expect($result->bestRate->pos_name)->toBe('Garanti')
        ->and((float) $result->bestRate->commission_rate)->toBe(0.027)
        ->and($result->cost)->toBe(27.0);
});

it('selects higher priority POS when costs are equal', function () {
    $strategy = new LowestCostSelectionStrategy(new PosRateRepository(new PosRate));

    // Akbank (0.028, priority 5) vs YapiKredi (0.028, priority 7)
    $request = new PosSelectionCriteria(
        amount: 1000,
        installment: 6,
        currency: Currency::TRY,
        cardType: CardType::Credit,
        cardBrand: null,
    );

    $result = $strategy->select($request);

    // Garanti wins overall (0.027), but if we exclude Garanti by filtering brand:
    $requestWorld = new PosSelectionCriteria(
        amount: 1000,
        installment: 6,
        currency: Currency::TRY,
        cardType: CardType::Credit,
        cardBrand: 'world',
    );

    $resultWorld = $strategy->select($requestWorld);

    expect($resultWorld->bestRate->pos_name)->toBe('YapiKredi');
});

it('filters by card_brand when provided', function () {
    $strategy = new LowestCostSelectionStrategy(new PosRateRepository(new PosRate));

    $request = new PosSelectionCriteria(
        amount: 1000,
        installment: 6,
        currency: Currency::TRY,
        cardType: CardType::Credit,
        cardBrand: 'axess',
    );

    $result = $strategy->select($request);

    expect($result->bestRate->pos_name)->toBe('Akbank')
        ->and($result->bestRate->card_brand)->toBe('axess');
});

it('throws PosRateNotFoundException when no match exists', function () {
    $strategy = new LowestCostSelectionStrategy(new PosRateRepository(new PosRate));

    $request = new PosSelectionCriteria(
        amount: 1000,
        installment: 12,
        currency: Currency::USD,
        cardType: CardType::Debit,
    );

    $strategy->select($request);
})->throws(PosRateNotFoundException::class);

it('builds correct filters in result', function () {
    $strategy = new LowestCostSelectionStrategy(new PosRateRepository(new PosRate));

    $request = new PosSelectionCriteria(
        amount: 500,
        installment: 6,
        currency: Currency::TRY,
        cardType: CardType::Credit,
        cardBrand: 'bonus',
    );

    $result = $strategy->select($request);

    expect($result->filters)->toBe([
        'installment' => 6,
        'currency' => 'TRY',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
    ]);
});
