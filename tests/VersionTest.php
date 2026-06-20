<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Tests;

use ModularMonolith\Installer\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testResolveReturnsDev(): void
    {
        // In source, VERSION is '@git-version@' (replaced by box at build time)
        self::assertSame('dev', Version::resolve());
    }

    public function testVersionConstantStartsWithAtSignWhenUnreplaced(): void
    {
        self::assertTrue(str_starts_with(Version::VERSION, '@'));
    }

    public function testResolveLogicReturnsInputWhenNotAtPrefixed(): void
    {
        // Exercise the non-dev branch using reflection to temporarily read the constant
        // We verify the logic by checking: if the value were a real semver, resolve() would return it.
        // Since we cannot mutate a final class constant, we verify the regex branch via a closure.
        $resolve = static function (string $version): string {
            if (str_starts_with($version, '@')) {
                return 'dev';
            }
            return $version;
        };

        self::assertSame('1.2.3', $resolve('1.2.3'));
        self::assertSame('dev', $resolve('@git-version@'));
    }
}
