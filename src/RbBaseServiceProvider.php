<?php

declare(strict_types=1);

namespace Raidboxes\RbBase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Raidboxes\RbBase\Commands\RbBaseInstallCommand;

class RbBaseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-rb-base')
            ->hasConfigFile([
                'raidboxes',
                'streamer',
                'sentry',
            ])
            ->hasRoute('api')
            ->hasViews()
            ->hasMigration('create_laravel-rb-base_table');

        $this->addCustomInstallCommand($package);
    }

    private function addCustomInstallCommand(Package $package): void
    {
        $installCommand = new RbBaseInstallCommand($package);

        $callable = function(RbBaseInstallCommand $command) {
            $command
                ->publishConfigFile()
                ->publishMigrations()
                ->endWith(function (RbBaseInstallCommand $command) {
                    $command->info('Project is set up!');
                });
        };

        $callable($installCommand);

        $package->consoleCommands[] = $installCommand;;
    }

    public function bootingPackage(): void
    {
        if (config('raidboxes.exclude_unvalidated_array_keys_from_request_data') === true) {
            Validator::excludeUnvalidatedArrayKeys();
        }

        $currentEnv = $this->app->environment();
        $allowedEnvironments = config('raidboxes.lazy_loading_allowed');
        if (!is_array($allowedEnvironments)) {
            $allowedEnvironments = [];
        }

        Model::preventLazyLoading(!in_array($currentEnv, $allowedEnvironments));
    }
}
