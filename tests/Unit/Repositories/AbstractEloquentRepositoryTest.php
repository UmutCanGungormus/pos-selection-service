<?php

use App\Models\PosRate;
use App\Repositories\Eloquent\PosRateRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->repository = new PosRateRepository(new PosRate);
});

function createPosRate(array $overrides = []): PosRate
{
    return PosRate::create(array_merge([
        'pos_name' => 'TestPOS',
        'card_type' => 'credit',
        'card_brand' => 'testbrand',
        'installment' => 1,
        'currency' => 'TRY',
        'commission_rate' => 0.025,
        'min_fee' => 0,
        'priority' => 5,
    ], $overrides));
}

// --- findById ---

it('findById returns model when it exists', function () {
    $rate = createPosRate();

    $found = $this->repository->findById($rate->id);

    expect($found)->toBeInstanceOf(PosRate::class)
        ->and($found->id)->toBe($rate->id);
});

it('findById returns null for non-existent id', function () {
    $found = $this->repository->findById(99999);

    expect($found)->toBeNull();
});

// --- findAll ---

it('findAll returns collection of all records', function () {
    createPosRate(['pos_name' => 'POS1']);
    createPosRate(['pos_name' => 'POS2']);
    createPosRate(['pos_name' => 'POS3']);

    $all = $this->repository->findAll();

    expect($all)->toBeInstanceOf(Collection::class)
        ->and($all)->toHaveCount(3);
});

it('findAll returns empty collection when no records exist', function () {
    $all = $this->repository->findAll();

    expect($all)->toBeInstanceOf(Collection::class)
        ->and($all)->toBeEmpty();
});

// --- create ---

it('create persists model to database', function () {
    $rate = $this->repository->create([
        'pos_name' => 'CreatedPOS',
        'card_type' => 'debit',
        'card_brand' => 'newbrand',
        'installment' => 3,
        'currency' => 'USD',
        'commission_rate' => 0.015,
        'min_fee' => 0.50,
        'priority' => 2,
    ]);

    expect($rate)->toBeInstanceOf(PosRate::class)
        ->and($rate->exists)->toBeTrue()
        ->and($rate->pos_name)->toBe('CreatedPOS');

    expect(PosRate::where('pos_name', 'CreatedPOS')->exists())->toBeTrue();
});

// --- update ---

it('update modifies model attributes', function () {
    $rate = createPosRate(['pos_name' => 'OriginalName']);

    $updated = $this->repository->update($rate->id, [
        'pos_name' => 'UpdatedName',
        'commission_rate' => 0.050,
    ]);

    expect($updated->pos_name)->toBe('UpdatedName')
        ->and((float) $updated->commission_rate)->toBe(0.050);
});

it('update throws exception for non-existent id', function () {
    $this->repository->update(99999, ['pos_name' => 'NoSuchRecord']);
})->throws(ModelNotFoundException::class);

// --- delete ---

it('delete removes model from database', function () {
    $rate = createPosRate();
    $id = $rate->id;

    $result = $this->repository->delete($id);

    expect($result)->toBeTrue()
        ->and(PosRate::find($id))->toBeNull();
});

it('delete throws exception for non-existent id', function () {
    $this->repository->delete(99999);
})->throws(ModelNotFoundException::class);

// --- exists ---

it('exists returns true when matching records found', function () {
    createPosRate(['pos_name' => 'ExistingPOS']);

    expect($this->repository->exists(['pos_name' => 'ExistingPOS']))->toBeTrue();
});

it('exists returns false when no matching records', function () {
    expect($this->repository->exists(['pos_name' => 'GhostPOS']))->toBeFalse();
});

// --- paginate ---

it('paginate returns LengthAwarePaginator', function () {
    for ($i = 0; $i < 10; $i++) {
        createPosRate(['pos_name' => "POS_{$i}"]);
    }

    $result = $this->repository->paginate(3);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3)
        ->and($result->total())->toBe(10)
        ->and($result->lastPage())->toBe(4);
});

it('paginate defaults to 15 per page', function () {
    for ($i = 0; $i < 20; $i++) {
        createPosRate(['pos_name' => "POS_{$i}"]);
    }

    $result = $this->repository->paginate();

    expect($result->perPage())->toBe(15)
        ->and($result->count())->toBe(15);
});

// --- findWhere ---

it('findWhere returns filtered results', function () {
    createPosRate(['pos_name' => 'CreditPOS', 'card_type' => 'credit']);
    createPosRate(['pos_name' => 'DebitPOS', 'card_type' => 'debit']);
    createPosRate(['pos_name' => 'AnotherCredit', 'card_type' => 'credit']);

    $results = $this->repository->findWhere(['card_type' => 'credit']);

    expect($results)->toHaveCount(2)
        ->and($results->pluck('pos_name')->toArray())
        ->toContain('CreditPOS', 'AnotherCredit');
});

it('findWhere returns empty collection when no match', function () {
    createPosRate(['currency' => 'TRY']);

    $results = $this->repository->findWhere(['currency' => 'EUR']);

    expect($results)->toBeEmpty();
});

// --- count ---

it('count returns total number of records', function () {
    createPosRate(['pos_name' => 'A']);
    createPosRate(['pos_name' => 'B']);
    createPosRate(['pos_name' => 'C']);

    expect($this->repository->count())->toBe(3);
});

it('count with criteria returns filtered count', function () {
    createPosRate(['pos_name' => 'A', 'card_type' => 'credit']);
    createPosRate(['pos_name' => 'B', 'card_type' => 'debit']);
    createPosRate(['pos_name' => 'C', 'card_type' => 'credit']);

    expect($this->repository->count(['card_type' => 'credit']))->toBe(2)
        ->and($this->repository->count(['card_type' => 'debit']))->toBe(1);
});

it('count returns zero when empty', function () {
    expect($this->repository->count())->toBe(0);
});

// --- chunk ---

it('chunk processes records in batches', function () {
    for ($i = 0; $i < 10; $i++) {
        createPosRate(['pos_name' => "POS_{$i}"]);
    }

    $processedCount = 0;
    $chunkSizes = [];

    $this->repository->chunk(3, function ($records) use (&$processedCount, &$chunkSizes) {
        $chunkSizes[] = $records->count();
        $processedCount += $records->count();

        return true;
    });

    expect($processedCount)->toBe(10)
        ->and($chunkSizes)->toBe([3, 3, 3, 1]);
});

it('chunk stops when callback returns false', function () {
    for ($i = 0; $i < 10; $i++) {
        createPosRate(['pos_name' => "POS_{$i}"]);
    }

    $processedChunks = 0;

    $this->repository->chunk(3, function () use (&$processedChunks) {
        $processedChunks++;

        return false; // stop after first chunk
    });

    expect($processedChunks)->toBe(1);
});

// --- findByField ---

it('findByField returns matching records', function () {
    createPosRate(['pos_name' => 'TargetPOS', 'installment' => 6]);
    createPosRate(['pos_name' => 'OtherPOS', 'installment' => 3]);
    createPosRate(['pos_name' => 'AlsoTarget', 'installment' => 6]);

    $results = $this->repository->findByField('installment', 6);

    expect($results)->toHaveCount(2)
        ->and($results->pluck('pos_name')->toArray())
        ->toContain('TargetPOS', 'AlsoTarget');
});

// --- firstOrCreate ---

it('firstOrCreate returns existing record', function () {
    $existing = createPosRate(['pos_name' => 'ExistingPOS']);

    $result = $this->repository->firstOrCreate(
        ['pos_name' => 'ExistingPOS'],
        ['commission_rate' => 0.099],
    );

    expect($result->id)->toBe($existing->id)
        ->and(PosRate::count())->toBe(1);
});

it('firstOrCreate creates new record when not found', function () {
    $result = $this->repository->firstOrCreate(
        ['pos_name' => 'BrandNewPOS'],
        [
            'card_type' => 'credit',
            'card_brand' => 'new',
            'installment' => 1,
            'currency' => 'TRY',
            'commission_rate' => 0.010,
            'min_fee' => 0,
            'priority' => 0,
        ],
    );

    expect($result->pos_name)->toBe('BrandNewPOS')
        ->and($result->exists)->toBeTrue()
        ->and(PosRate::count())->toBe(1);
});

// --- updateOrCreate ---

it('updateOrCreate updates existing record', function () {
    createPosRate(['pos_name' => 'ExistingPOS', 'commission_rate' => 0.020]);

    $result = $this->repository->updateOrCreate(
        ['pos_name' => 'ExistingPOS'],
        ['commission_rate' => 0.050],
    );

    expect((float) $result->commission_rate)->toBe(0.050)
        ->and(PosRate::count())->toBe(1);
});

it('updateOrCreate creates new record when not found', function () {
    $result = $this->repository->updateOrCreate(
        ['pos_name' => 'NewPOS'],
        [
            'card_type' => 'debit',
            'card_brand' => 'brand',
            'installment' => 1,
            'currency' => 'EUR',
            'commission_rate' => 0.010,
            'min_fee' => 0,
            'priority' => 0,
        ],
    );

    expect($result->pos_name)->toBe('NewPOS')
        ->and($result->exists)->toBeTrue();
});
