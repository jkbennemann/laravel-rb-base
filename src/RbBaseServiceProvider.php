<?php

declare(strict_types=1);

namespace Raidboxes\RbBase;

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
            ->hasViews()
            ->hasMigration('create_laravel-rb-base_table');

        $this->addCustomInstallCommand($package);
    }

    private function addCustomInstallCommand(Package $package): void
    {
        $installCommand = new RbBaseInstallCommand($package);

        $callable = function(RbBaseInstallCommand $command) {
            $command
                ->startWith(function (RbBaseInstallCommand $command) {
                    $command->info('Hello, and welcome to my great new package!');
                })
                ->endWith(function (RbBaseInstallCommand $command) {
                    $command->info('Have a great day!');
                });
        };

        $callable($installCommand);

        $package->consoleCommands[] = $installCommand;;
    }
}
