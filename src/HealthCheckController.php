<?php

declare(strict_types=1);

namespace Raidboxes\RbBase;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthCheckController extends Controller
{
    private const OK = 'ok';
    private const NOT_OK = 'not_ok';

    public function simpleCheck(): JsonResponse
    {
        return response()->json(['service' => self::OK]);
    }

    public function extendedCheck(): JsonResponse
    {
        $check = [];
        $statusCode = Response::HTTP_OK;
        if (config('health_check.database')) {
            $check['database'] = [
                'status' => self::NOT_OK,
            ];
            try {
                $check['database']['status'] = DB::connection()->getPdo() ? self::OK : self::NOT_OK;
            } catch (Throwable $exception) {
                $check['database']['error'] = $exception->getMessage();
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }
        }

        if (config('health_check.cache')) {
            $check['cache'] = [
                'status' => self::NOT_OK,
            ];
            try {
                $check['cache']['status'] = Cache::put('healthCheck', self::OK, rand(1, 5)) ? self::OK : self::NOT_OK;
            } catch (Throwable $exception) {
                $check['cache']['error'] = $exception->getMessage();
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }
        }

        return response()->json($check)->setStatusCode($statusCode);
    }
}
