<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Raidboxes\RbBase\RbBase
 */
class RbBase extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Raidboxes\RbBase\RbBase::class;
    }
}
