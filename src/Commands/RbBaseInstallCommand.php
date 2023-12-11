<?php

declare(strict_types=1);

namespace Raidboxes\RbBase\Commands;

use Illuminate\Filesystem\Filesystem;
use Raidboxes\LaravelJwtAuthentication\Providers\Jwt\Lcobucci;
use Raidboxes\RbBase\ComposerConfiguration;
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

        $this->configureErrorHandler();

        $this->configureDomains();

        $this->updateComposerJson();

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

        if ($this->endWith) {
            ($this->endWith)($this);
        }

        return 0;
    }

    private function configureErrorHandler(): void
    {
        $fileHasChanged = $this->hasFileChanged(__DIR__ . '/../Handler.php.stub', 'app/Exceptions/Handler.php');

        if ($fileHasChanged) {
            if ($this->confirm('Do you want to replace the Handler.php file?', false)) {
                copy(__DIR__ . '/../Handler.php.stub', base_path('app/Exceptions/Handler.php'));

                return;
            }

            $this->info('Skipped replacing Handler.php');
        }
    }

    private function configureDomains(): void
    {
        $filesystem = new Filesystem();

        //set jwt-authentication.algorithm to ALGORITHM_RS256
        $filesystem->ensureDirectoryExists('domain/Common/Entity');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/DTO');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Events');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Integration');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Enums');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Traits');
        $filesystem->ensureDirectoryExists('domain/Raidboxes/Requests');

        $this->ensureGitKeep();
    }

    private function ensureGitKeep(): void
    {
        $filesystem = new Filesystem();
        $directories = [
            'domain/Common/Entity',
            'domain/Raidboxes/DTO',
            'domain/Raidboxes/Events',
            'domain/Raidboxes/Integration',
            'domain/Raidboxes/Enums',
            'domain/Raidboxes/Traits',
            'domain/Raidboxes/Requests',
        ];

        foreach ($directories as $directory) {
            if ($filesystem->isEmptyDirectory($directory)) {
                $filesystem->put($directory . '/.gitkeep', '');
            }
        }
    }

    private function configureJwt(): void
    {
        $filesystem = new Filesystem();
        $certificatePath = 'storage/certs';
        $privateKeyName = 'rs256.key';
        $publicKeyName = 'rs256.key.pub';

        $filesystem->ensureDirectoryExists($certificatePath);

        $privateKeyExists = $filesystem->exists($certificatePath . '/' . $privateKeyName);

        if ($privateKeyExists) {
            if (!$this->confirm('Do you want to regenerate your RSA Key for JWT Authentication?', false)) {
                return;
            }

            $filesystem->delete(base_path($certificatePath . '/' . $privateKeyName));
            $filesystem->delete(base_path($certificatePath . '/' . $publicKeyName));
        }

        $this->executeShell('ssh-keygen -t rsa -b 4096 -m PEM -f ' . base_path($certificatePath . '/' . $privateKeyName) . ' -q -N ""');
        $this->executeShell('openssl rsa -in ' . base_path($certificatePath . '/' . $privateKeyName) . ' -pubout -outform PEM -out ' . base_path($certificatePath . '/' . $publicKeyName));

        //update .env settings
        $this->changeEnvironmentVariable('JWT_ALGORITHM', Lcobucci::ALGORITHM_RS256);
        $this->changeEnvironmentVariable('JWT_PRIVATE_KEY', "\"" . './' . $certificatePath . '/' . $privateKeyName . "\"");
        $this->changeEnvironmentVariable('JWT_PUBLIC_KEY', "\"" . './' . $certificatePath . '/' . $publicKeyName . "\"");
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

    private function hasFileChanged(string $source, string $destination): bool
    {
        $filesystem = new Filesystem();
        $file1 = $filesystem->get($source);
        $file2 = $filesystem->get($destination);

        return md5($file1) != md5($file2);
    }

    private function updateComposerJson(): void
    {
        $filesystem = new Filesystem();
        $applicationComposer = json_decode($filesystem->get('composer.json'), true);
        $config = new ComposerConfiguration();
        $neededDependencies = $config->dependencies();
        $neededDevDependencies = $config->devDependencies();
        $neededRepositories = $config->repositories();
        $neededAutoloadPsr4 = $config->autoloadPsr4();
        $neededAutoloadPsr4Dev = $config->autoloadPsr4Dev();

        //repositories
        $repositories = collect($applicationComposer['repositories'] ?? []);
        $setRepositories = $repositories->filter(function(array $data) {
            return $data['url'] ?? null;
        })->map(function(array $data) {
            return $data['url'];
        })->toArray();

        foreach ($neededRepositories as $repositoryData) {
            if (!in_array($repositoryData['url'], $setRepositories)) {
                $repositories->add([
                    'type' => $repositoryData['type'],
                    'url' => $repositoryData['url'],
                ]);
            }
        }

        //dependencies
        $dependencies = collect($applicationComposer['require'] ?? []);
        $setDependencies = $dependencies->map(function(string $value, string $package) {
            return $package;
        })->flatten()->toArray();

        foreach ($neededDependencies as $package => $version) {
            if (!in_array($package, $setDependencies)) {
                $dependencies->put($package, $version);
            }
        }

        //dev dependencies
        $devDependencies = collect($applicationComposer['require-dev'] ?? []);
        $setDependencies = $devDependencies->map(function(string $value, string $package) {
            return $package;
        })->flatten()->toArray();

        foreach ($neededDevDependencies as $package => $version) {
            if (!in_array($package, $setDependencies)) {
                $devDependencies->put($package, $version);
            }
        }

        //autoload
        $autoloadPsr4 = collect($applicationComposer['autoload']['psr-4'] ?? []);
        foreach ($neededAutoloadPsr4 as $namespace => $directory) {
            if (!array_key_exists($namespace, $autoloadPsr4->toArray())) {
                $autoloadPsr4->put($namespace, $directory);
            }
        }

        $autoloadPsr4Dev = collect($applicationComposer['autoload-dev']['psr-4'] ?? []);
        foreach ($neededAutoloadPsr4Dev as $namespace => $directory) {
            if (!array_key_exists($namespace, $autoloadPsr4Dev->toArray())) {
                $autoloadPsr4Dev->put($namespace, $directory);
            }
        }

        $applicationComposer['repositories'] = $repositories->toArray();
        $applicationComposer['license'] = $config->license();
        $applicationComposer['version'] = $applicationComposer['version'] ?? $config->version();
        $applicationComposer['type'] = $applicationComposer['type'] ?? $config->type();
        $applicationComposer['require'] = $dependencies->toArray();
        $applicationComposer['require-dev'] = $devDependencies->toArray();
        $applicationComposer['autoload']['psr-4'] = $autoloadPsr4->toArray();
        $applicationComposer['autoload-dev']['psr-4'] = $autoloadPsr4Dev->toArray();

        $filesystem->replace('composer.json', json_encode($applicationComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
