<?php

declare(strict_types=1);

namespace ModularMonolith\Installer;

final class Version
{
    public const string VERSION = '@git-version@';

    public static function resolve(): string
    {
        $version = self::VERSION;

        if (str_starts_with($version, '@')) {
            return 'dev';
        }

        return $version;
    }
}
