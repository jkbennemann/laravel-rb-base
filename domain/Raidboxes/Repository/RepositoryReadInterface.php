<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryReadInterface
{
    public const MAX_PAGINATION = 50;

    public const SIMPLE_PAGINATION = 50;

    public function all(): Collection;

    public function allWithPagination(int $perPage = self::MAX_PAGINATION): LengthAwarePaginator;

    public function find($id): Model;

    public function findBy(array $attributes = []): Collection;

    public function findByWithPagination(
        array $attributes = [],
        int $perPage = self::SIMPLE_PAGINATION
    ): LengthAwarePaginator;

    public function findOneBy(array $attributes = []): ?Model;
}
