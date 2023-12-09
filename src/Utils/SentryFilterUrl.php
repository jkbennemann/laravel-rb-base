<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Utils;

use Illuminate\Support\Facades\Request;
use Sentry\Tracing\SamplingContext;

class SentryFilterUrl
{
    public static function filter(SamplingContext $context): float
    {
        $excludePaths = config('app.traces_sampler_exclude', []);

        $path = $context->getTransactionContext()->getData()['url'] ?? Request::path();
        foreach (array_merge($excludePaths, ['health-check', 'health-check-extended', 'telescope']) as $excludePath) {
            if (str_contains($path, $excludePath)) {
                return 0.0;
            }
        }

        return (float) config('sentry.traces_sample_rate', 0.0);
    }
}
