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
        $envPath = $targetPath . '/.env';

        if (!$this->filesystem->exists($envPath)) {
            return;
        }

        $contents = (string) file_get_contents($envPath);
        $contents = (string) preg_replace(
            pattern: '/^APP_SECRET=.*$/m',
            replacement: 'APP_SECRET=' . $appSecret,
            subject: $contents,
        );

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
