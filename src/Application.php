<?php

declare(strict_types=1);

namespace ModularMonolith\Installer;

use ModularMonolith\Installer\Command\NewCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Modular Monolith Installer', Version::resolve());
        $this->addCommand(new NewCommand());
    }
}
