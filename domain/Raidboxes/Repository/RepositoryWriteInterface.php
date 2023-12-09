<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\Repository;

use Illuminate\Database\Eloquent\Model;

interface RepositoryWriteInterface
{
    public function push(Model $model): ?Model;

    public function save(Model $model): ?Model;

    public function update(array $data, $model): bool;

    public function delete($model): bool;
}
