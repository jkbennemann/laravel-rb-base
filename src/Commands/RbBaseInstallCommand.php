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

        if ($filesystem->isEmptyDirectory('domain/Common/Entity')) {
            $filesystem->put('domain/Common/Entity/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/DTO')) {
            $filesystem->put('domain/Raidboxes/DTO/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/Events')) {
            $filesystem->put('domain/Raidboxes/Events/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/Integration')) {
            $filesystem->put('domain/Raidboxes/Integration/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/Enums')) {
            $filesystem->put('domain/Raidboxes/Enums/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/Traits')) {
            $filesystem->put('domain/Raidboxes/Traits/.gitkeep', '');
        }

        if ($filesystem->isEmptyDirectory('domain/Raidboxes/Requests')) {
            $filesystem->put('domain/Raidboxes/Requests/.gitkeep', '');
        }
    }

    private function configureJwt(): void
    {
        $filesystem = new Filesystem();

        $filesystem->ensureDirectoryExists('storage/certs');

        $privateKeyExists = $filesystem->exists('storage/certs/rs256.key');

        if ($privateKeyExists) {
            if (!$this->confirm('Do you want to regenerate your RSA Key for JWT Authentication?', false)) {
                return;
            }

            $filesystem->delete(base_path('storage/certs/rs256.key'));
            $filesystem->delete(base_path('storage/certs/rs256.key.pub'));
        }

        $this->executeShell('ssh-keygen -t rsa -b 4096 -m PEM -f ' . base_path('storage/certs/rs256.key') . ' -q -N ""');
        $this->executeShell('openssl rsa -in ' . base_path('storage/certs/rs256.key') . ' -pubout -outform PEM -out ' . base_path('storage/certs/rs256.key.pub'));

        //update .env settings
        $this->changeEnvironmentVariable('JWT_ALGORITHM', Lcobucci::ALGORITHM_RS256);
        $this->changeEnvironmentVariable('JWT_PRIVATE_KEY', "\"" . './storage/certs/rs256.key' . "\"");
        $this->changeEnvironmentVariable('JWT_PUBLIC_KEY', "\"" . './storage/certs/rs256.key.pub' . "\"");
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
        $reposToAdd = [];
        //repositories
        $urls = collect($applicationComposer['repositories'] ?? [])->filter(function(array $data) {
            return $data['url'] ?? null;
        })->map(function(array $data) {
            return $data['url'];
        })->toArray();

        //jwt dependency
        if (!in_array('git@gitlab.com:raidboxes/packages/laravel-jwt-authentication.git', $urls)) {
            $reposToAdd[] = [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/laravel-jwt-authentication.git',
            ];
        }

        //schema dto dependency
        if (!in_array('git@gitlab.com:raidboxes/packages/schema-dto.git', $urls)) {
            $reposToAdd[] = [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/schema-dto.git',
            ];
        }

        //laravel-phpcs
        if (!in_array('git@gitlab.com:raidboxes/packages/laravel-phpcs.git', $urls)) {
            $reposToAdd[] = [
                'type' => 'vcs',
                'url' => 'git@gitlab.com:raidboxes/packages/laravel-phpcs.git',
            ];
        }

        $applicationComposer['repositories'] = array_merge(
            $applicationComposer['repositories'],
            $reposToAdd
        );
        $applicationComposer['license'] = 'proprietary';
        $applicationComposer['version'] = $applicationComposer['version'] ?? '0.0.1';
        $applicationComposer['type'] = $applicationComposer['type'] ?? 'project';

        $dependenciesToAdd = [];
        $urls = collect($applicationComposer['require'] ?? [])->map(function(string $value, string $package) {
            return $package;
        })->flatten()->toArray();

        if (!in_array('raidboxes/schema-dto', $urls)) {
            $devDependenciesToAdd['raidboxes/schema-dto'] = '^1.2.0';
        }

        if (!in_array('sentry/sentry-laravel', $urls)) {
            $devDependenciesToAdd['sentry/sentry-laravel'] = '^3.2.0';
        }

        if (!in_array('raidboxes/laravel-jwt-authentication', $urls)) {
            $devDependenciesToAdd['raidboxes/laravel-jwt-authentication'] = '^2.0';
        }

        if (!in_array('prwnr/laravel-streamer', $urls)) {
            $devDependenciesToAdd['prwnr/laravel-streamer'] = '^3.4';
        }

        if (!in_array('onecentlin/laravel-adminer', $urls)) {
            $devDependenciesToAdd['onecentlin/laravel-adminer'] = '^6.0';
        }

        $applicationComposer['require'] = array_merge(
            $applicationComposer['require'],
            $dependenciesToAdd
        );

        $devDependenciesToAdd = [];
        $urls = collect($applicationComposer['require-dev'] ?? [])->map(function(string $value, string $package) {
            return $package;
        })->flatten()->toArray();

        if (!in_array('spatie/laravel-ray', $urls)) {
            $devDependenciesToAdd['spatie/laravel-ray'] = '^1.33';
        }

        if (!in_array('raidboxes/laravel-phpcs', $urls)) {
            $devDependenciesToAdd['raidboxes/laravel-phpcs'] = 'dev-main';
        }

        if (!in_array('laravel/sail', $urls)) {
            $devDependenciesToAdd['laravel/sail'] = '^1.22';
        }

        $applicationComposer['require-dev'] = array_merge(
            $applicationComposer['require-dev'],
            $devDependenciesToAdd
        );

        $filesystem->replace('composer.json', json_encode($applicationComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
