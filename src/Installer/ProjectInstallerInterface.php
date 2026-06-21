<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Installer;

interface ProjectInstallerInterface
{
    public function createProject(string $directory, string $repository, callable $onOutput): bool;

    public function writeAppSecret(string $targetPath, string $appSecret): void;

    public function writeComposeProjectName(string $targetPath, string $prefix): void;

    public function removeTodoListExample(string $targetPath): void;
}
