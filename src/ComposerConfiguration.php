<?php

declare(strict_types=1);

namespace Raidboxes\RbBase;

class ComposerConfiguration
{
    public function dependencies(): array
    {
        return [
            'raidboxes/schema-dto'                  => '^1.2.0',
            'raidboxes/laravel-jwt-authentication'  => '^2.0',
            'sentry/sentry-laravel'                 => '^3.2.0',
            'prwnr/laravel-streamer'                => '^3.4',
            'onecentlin/laravel-adminer'            => '^6.0',
        ];
    }

    public function devDependencies(): array
    {
        return [
            'raidboxes/laravel-phpcs'   => 'dev-main',
            'laravel/sail'              => '^1.22',
            'spatie/laravel-ray'        => '^1.33',
        ];
    }

    public function repositories(): array
    {
        return [
            'raidboxes/laravel-phpcs' => [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/laravel-phpcs.git',
            ],
            'raidboxes/schema-dto' => [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/schema-dto.git',
            ],
            'raidboxes/laravel-jwt-authentication' => [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/laravel-jwt-authentication.git',
            ],
        ];
    }

    public function autoloadPsr4(): array
    {
        return [
            'Domain\\' => 'domain/',
        ];
    }

    public function autoloadPsr4Dev(): array
    {
        return [
            'Tests\\' => 'tests/',
        ];
    }

    public function license(): string
    {
        return 'proprietary';
    }

    public function version(): string
    {
        return '0.0.1';
    }

    public function type(): string
    {
        return 'project';
    }
}
