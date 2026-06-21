<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Installer;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ProjectInstaller
{
    public const string DEFAULT_TEMPLATE = 'modular-monolith/symfony-application';

    public const string DEFAULT_REPOSITORY = 'https://github.com/ModularMonolith/Symfony-Application';

    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function createProject(string $directory, string $repository, callable $onOutput): bool
    {
        $process = $this->buildProcess(directory: $directory, repository: $repository);
        $process->setTimeout(null);
        $process->run($onOutput);

        return $process->isSuccessful();
    }

    public function writeAppSecret(string $targetPath, string $appSecret): void
    {
        $this->writeEnvVar($targetPath, 'APP_SECRET', $appSecret);
    }

    public function writeComposeProjectName(string $targetPath, string $projectName): void
    {
        $sanitized = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $projectName));
        $sanitized = trim($sanitized, '-');

        $this->writeEnvVar($targetPath, 'COMPOSE_PROJECT_NAME', $sanitized);
    }

    private function writeEnvVar(string $targetPath, string $key, string $value): void
    {
        $envPath = $targetPath . '/.env';

        if (!$this->filesystem->exists($envPath)) {
            return;
        }

        $contents = (string) file_get_contents($envPath);

        if (preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
            $contents = (string) preg_replace(
                pattern: '/^' . preg_quote($key, '/') . '=.*$/m',
                replacement: $key . '=' . $value,
                subject: $contents,
            );
        } else {
            $contents .= PHP_EOL . $key . '=' . $value . PHP_EOL;
        }

        $this->filesystem->dumpFile($envPath, $contents);
    }

    public function removeTodoListExample(string $targetPath): void
    {
        $paths = [
            $targetPath . '/src/Capability/TodoList',
            $targetPath . '/tests/Capability/TodoList',
        ];

        foreach ($paths as $path) {
            if ($this->filesystem->exists($path)) {
                $this->filesystem->remove($path);
            }
        }
    }

    protected function buildProcess(string $directory, string $repository): Process
    {
        return new Process(command: [
            'composer',
            'create-project',
            self::DEFAULT_TEMPLATE,
            $directory,
            '--repository=' . json_encode(
                value: ['type' => 'vcs', 'url' => $repository],
                flags: JSON_THROW_ON_ERROR,
            ),
        ]);
    }
}
