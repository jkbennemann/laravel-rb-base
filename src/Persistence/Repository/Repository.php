<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Persistence\Repository;

use Closure;
use Raidboxes\Domain\Raidboxes\Repository\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Repository implements RepositoryInterface
{
    public function all(): Collection
    {
        return $this->model
            ->orderBy($this->model->getKeyName())
            ->limit(self::MAX_PAGINATION)
            ->get();
    }

    public function allWithPagination(int $perPage = self::MAX_PAGINATION): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find($id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function findBy(array|Closure $attributes = []): Collection
    {
        return $this->model
            ->orderBy($this->model->getKeyName())
            ->where($attributes)
            ->limit(self::MAX_PAGINATION)
            ->get();
    }

    public function findByWithPagination(
        array $attributes = [],
        int $perPage = self::SIMPLE_PAGINATION
    ): LengthAwarePaginator {
        return $this->model->where($attributes)->paginate($perPage);
    }

    public function findOneBy(array|Closure $attributes = []): ?Model
    {
        return $this->model->where($attributes)->first();
    }

    public function save(Model $model): Model
    {
        return $model->save() ? $model : throw new RuntimeException();
    }

    public function push(Model $model): Model
    {
        return $model->push() ? $model : throw new RuntimeException();
    }

    public function update(array $data, $model): bool
    {
        if (!$model instanceof Model) {
            $model = $this->model->find($model);
        }

        return $model?->update($data) ?? false;
    }

    public function delete($model): bool
    {
        if (!$model instanceof Model) {
            $model = $this->model->find($model);
        }

        return $model?->delete() ?? false;
    }

    public function __call($method, $parameters)
    {
        $this->model->$method($parameters);
    }
}
