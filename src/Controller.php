<?php

declare(strict_types=1);

namespace Raidboxes\RbBase;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    //phpcs:ignore PSR12.Traits.UseDeclaration.MultipleImport
    use AuthorizesRequests, ValidatesRequests;
}
