<?php

use App\Enums\CardType;
use App\Enums\Currency;
use App\Models\PosRate;

it('calculates cost as amount times commission rate', function () {
    $rate = new PosRate([
        'pos_name' => 'Garanti',
        'card_type' => 'credit',
        'card_brand' => 'bonus',
        'installment' => 6,
        'currency' => 'TRY',
        'commission_rate' => 0.027,
        'min_fee' => 0,
    ]);

    expect($rate->calculateCost(1000))->toBe(27.0);
});

it('uses min_fee when commission cost is lower', function () {
    $rate = new PosRate([
        'pos_name' => 'KuveytTurk',
        'card_type' => 'credit',
        'card_brand' => 'saglam',
        'installment' => 3,
        'currency' => 'TRY',
        'commission_rate' => 0.02,
        'min_fee' => 2.00,
    ]);

    // 10 * 0.02 = 0.2, which is less than min_fee of 2.00
    expect($rate->calculateCost(10))->toBe(2.0);
    // 1000 * 0.02 = 20.0, which is greater than min_fee of 2.00
    expect($rate->calculateCost(1000))->toBe(20.0);
});

it('casts card_type to CardType enum', function () {
    $rate = new PosRate(['card_type' => 'credit']);

    expect($rate->card_type)->toBe(CardType::Credit);
});

it('casts currency to Currency enum', function () {
    $rate = new PosRate(['currency' => 'TRY']);

    expect($rate->currency)->toBe(Currency::TRY);
});
