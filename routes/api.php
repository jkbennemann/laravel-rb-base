<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Raidboxes\RbBase\HealthCheckController;

if (config('raidboxes.include_health_check') === true) {
    Route::get('/health-check', [HealthCheckController::class, 'simpleCheck']);
    Route::get('/health-check-extended', [HealthCheckController::class, 'extendedCheck']);
}
