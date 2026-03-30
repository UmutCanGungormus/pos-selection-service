<?php

namespace App\Repositories;

use App\Repositories\Contracts\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @template T of Model
 *
 * @implements EloquentRepositoryInterface<T>
 */
abstract class AbstractEloquentRepository implements EloquentRepositoryInterface
{
    /**
     * @param  T  $model
     */
    public function __construct(
        protected readonly Model $model,
    ) {}

    public function findById(int|string $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function findAll(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function findWhere(array $criteria): Collection
    {
        return $this->model->newQuery()->where($criteria)->get();
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(int|string $id, array $attributes): Model
    {
        $record = $this->model->newQuery()->findOrFail($id);
        $record->update($attributes);

        return $record->refresh();
    }

    public function delete(int|string $id): bool
    {
        $record = $this->model->newQuery()->findOrFail($id);

        return (bool) $record->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();

        if ($criteria !== []) {
            $query->where($criteria);
        }

        return $query->count();
    }

    public function exists(array $criteria): bool
    {
        return $this->model->newQuery()->where($criteria)->exists();
    }

    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->newQuery()->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->newQuery()->updateOrCreate($attributes, $values);
    }

    public function findByField(string $field, mixed $value): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get();
    }

    public function chunk(int $count, callable $callback): bool
    {
        return $this->model->newQuery()->chunk($count, $callback);
    }
}
