<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Tests;

use PHPUnit\Framework\TestCase;

abstract class InstallerTestCase extends TestCase
{
    protected function removeDir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeDir($full) : unlink($full);
        }

        rmdir($path);
    }
}
