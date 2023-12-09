<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Commands;

use Illuminate\Filesystem\Filesystem;
use Raidboxes\LaravelJwtAuthentication\Providers\Jwt\Lcobucci;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Symfony\Component\Process\Process;

class RbBaseInstallCommand extends InstallCommand
{
    public function __construct(Package $package)
    {
        parent::__construct($package);
    }

    public function handle(): int
    {
        if ($this->startWith) {
            ($this->startWith)($this);
        }

        $this->configureJwt();

        $this->configureDomains();

        foreach ($this->publishes as $tag) {
            $name = str_replace('-', ' ', $tag);
            $this->comment("Publishing {$name}...");

            $this->callSilently("vendor:publish", [
                '--tag' => "{$this->package->shortName()}-{$tag}",
            ]);
        }

        if ($this->askToRunMigrations) {
            if ($this->confirm('Would you like to run the migrations now?')) {
                $this->comment('Running migrations...');

                $this->call('migrate');
            }
        }

        if ($this->copyServiceProviderInApp) {
            $this->comment('Publishing service provider...');

            $this->copyServiceProviderInApp();
        }

        if ($this->starRepo) {
            if ($this->confirm('Would you like to star our repo on GitHub?')) {
                $repoUrl = "https://github.com/{$this->starRepo}";

                if (PHP_OS_FAMILY == 'Darwin') {
                    exec("open {$repoUrl}");
                }
                if (PHP_OS_FAMILY == 'Windows') {
                    exec("start {$repoUrl}");
                }
                if (PHP_OS_FAMILY == 'Linux') {
                    exec("xdg-open {$repoUrl}");
                }
            }
        }

        $this->info("{$this->package->shortName()} has been installed!");

        if ($this->endWith) {
            ($this->endWith)($this);
        }

        return 0;
    }

    private function configureDomains(): void
    {
        $this->info('Configuring Domain setup');
        $filesystem = new Filesystem();

        //set jwt-authentication.algorithm to ALGORITHM_RS256
        $filesystem->ensureDirectoryExists('domain/Common/Entity');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/DTO');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Events');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Integration');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Enums');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Traits');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Requests');
    }

    private function configureJwt(): void
    {
        $filesystem = new Filesystem();

        $filesystem->ensureDirectoryExists('storage/certs');

        $privateKeyExists = $filesystem->exists('storage/certs/rs256.key');

        if (!$privateKeyExists) {
            $this->executeShell('ssh-keygen -t rsa -b 4096 -m PEM -f ' . base_path('storage/certs/rs256.key') . ' -q -N ""');
            $this->executeShell('openssl rsa -in ' . base_path('storage/certs/rs256.key') . ' -pubout -outform PEM -out ' . base_path('storage/certs/rs256.key.pub'));

            //update .env settings
            $this->changeEnvironmentVariable('JWT_ALGORITHM', Lcobucci::ALGORITHM_RS256);
            $this->changeEnvironmentVariable('JWT_PRIVATE_KEY', "\"" . './storage/certs/rs256.key' . "\"");
            $this->changeEnvironmentVariable('JWT_PUBLIC_KEY', "\"" . './storage/certs/rs256.key.pub' . "\"");
        }
    }

    private function executeShell($cmd): string
    {
        $process = Process::fromShellCommandline($cmd);

        $processOutput = '';

        $captureOutput = function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        };

        $process->setTimeout(null)
            ->run($captureOutput);

        if ($process->getExitCode()) {
            $exception = new \Exception($cmd . " - " . $processOutput);
            report($exception);

            throw $exception;
        }

        return $processOutput;
    }

    private function changeEnvironmentVariable($key,$value)
    {
        $path = base_path('.env');

        $content = file_get_contents($path);
        $isPresent = stripos($content, $key . '=');

        if (!$isPresent) {
            $content .= "\n";
            $content .= $key . '=' . $value;
            file_put_contents($path, $content);
            return;
        }

        if(is_bool(env($key)))
        {
            $old = env($key)? 'true' : 'false';
        }
        elseif(env($key)===null){
            $old = 'null';
        }
        else{
            $old = env($key);
        }

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                "$key=".$old, "$key=".$value, file_get_contents($path)
            ));
        }
    }
}
