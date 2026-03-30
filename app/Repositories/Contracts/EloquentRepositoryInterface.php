<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of Model
 */
interface EloquentRepositoryInterface
{
    /**
     * @return T|null
     */
    public function findById(int|string $id): ?Model;

    /**
     * @return Collection<int, T>
     */
    public function findAll(): Collection;

    /**
     * @param  array<string, mixed>  $criteria
     * @return Collection<int, T>
     */
    public function findWhere(array $criteria): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @return T
     */
    public function update(int|string $id, array $attributes): Model;

    public function delete(int|string $id): bool;

    /**
     * @return LengthAwarePaginator<T>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function count(array $criteria = []): int;

    /**
     * @param  array<string, mixed>  $criteria
     */
    public function exists(array $criteria): bool;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return T
     */
    public function firstOrCreate(array $attributes, array $values = []): Model;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     * @return T
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * @return Collection<int, T>
     */
    public function findByField(string $field, mixed $value): Collection;

    public function chunk(int $count, callable $callback): bool;
}
