<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Tests\Command;

use ModularMonolith\Installer\Command\NewCommand;
use ModularMonolith\Installer\Installer\ProjectInstaller;
use ModularMonolith\Installer\Installer\ProjectInstallerInterface;
use ModularMonolith\Installer\Tests\InstallerTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class NewCommandTest extends InstallerTestCase
{
    private string $tempDir;
    private string $originalDir;

    protected function setUp(): void
    {
        $this->originalDir = (string) getcwd();
        $this->tempDir = sys_get_temp_dir() . '/modular-monolith-installer-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalDir);
        $this->removeDir($this->tempDir);
    }

    public function testFailsWhenDirectoryAlreadyExists(): void
    {
        mkdir($this->tempDir . '/already-exists');

        $tester = new CommandTester(new NewCommand($this->stubInstaller(fn () => true)));
        $tester->execute(['directory' => 'already-exists']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testSucceedsInNoInteractionModeWhenProcessSucceeds(): void
    {
        $installer = $this->stubInstaller(function (): bool {
            mkdir($this->tempDir . '/new-project', 0777, true);
            file_put_contents($this->tempDir . '/new-project/.env', "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->execute(['directory' => 'new-project', '--no-interaction' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Application created successfully', $tester->getDisplay());
    }

    public function testFailsWhenProcessFails(): void
    {
        $tester = new CommandTester(new NewCommand($this->stubInstaller(fn () => false)));
        $tester->execute(['directory' => 'failed-project', '--no-interaction' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('composer create-project failed', $tester->getDisplay());
    }

    public function testEnvSecretIsReplacedOnSuccess(): void
    {
        $envFile = $this->tempDir . '/secret-project/.env';

        $installer = $this->stubInstaller(function () use ($envFile): bool {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->execute(['directory' => 'secret-project', '--no-interaction' => true]);

        $envContents = (string) file_get_contents($envFile);
        self::assertStringNotContainsString('APP_SECRET=changeme', $envContents);
        self::assertMatchesRegularExpression('/^APP_SECRET=[a-f0-9]{32}$/m', $envContents);
    }

    public function testNoInteractionWritesDefaultComposeProjectName(): void
    {
        $envFile = $this->tempDir . '/my-project/.env';

        $installer = $this->stubInstaller(function () use ($envFile): bool {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->execute(['directory' => 'my-project', '--no-interaction' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertMatchesRegularExpression(
            '/^COMPOSE_PROJECT_NAME=my-project$/m',
            (string) file_get_contents($envFile),
        );
    }

    public function testInteractiveUsesDefaultContainerPrefixOnEmptyInput(): void
    {
        $envFile = $this->tempDir . '/my-project/.env';

        $installer = $this->stubInstaller(function () use ($envFile): bool {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->setInputs(['no', '', '']);
        $tester->execute(['directory' => 'my-project']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertMatchesRegularExpression(
            '/^COMPOSE_PROJECT_NAME=my-project$/m',
            (string) file_get_contents($envFile),
        );
    }

    public function testInteractiveAcceptsCustomContainerPrefix(): void
    {
        $envFile = $this->tempDir . '/my-project/.env';

        $installer = $this->stubInstaller(function () use ($envFile): bool {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->setInputs(['no', '', 'custom-prefix']);
        $tester->execute(['directory' => 'my-project']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertMatchesRegularExpression(
            '/^COMPOSE_PROJECT_NAME=custom-prefix$/m',
            (string) file_get_contents($envFile),
        );
    }

    public function testInteractiveAcceptsCustomAppSecret(): void
    {
        $envFile = $this->tempDir . '/my-project/.env';

        $installer = $this->stubInstaller(function () use ($envFile): bool {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->setInputs(['no', 'my-custom-secret', '']);
        $tester->execute(['directory' => 'my-project']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(
            'APP_SECRET=my-custom-secret',
            (string) file_get_contents($envFile),
        );
    }

    public function testInteractiveRemovesTodoListWhenConfirmed(): void
    {
        $projectDir = $this->tempDir . '/my-project';
        $todoSrcDir = $projectDir . '/src/Capability/TodoList';
        $todoTestDir = $projectDir . '/tests/Capability/TodoList';

        $installer = $this->stubInstaller(function () use ($projectDir, $todoSrcDir, $todoTestDir): bool {
            mkdir($todoSrcDir, 0777, true);
            mkdir($todoTestDir, 0777, true);
            file_put_contents($projectDir . '/.env', "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->setInputs(['yes', '', '']);
        $tester->execute(['directory' => 'my-project']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertDirectoryDoesNotExist($todoSrcDir);
        self::assertDirectoryDoesNotExist($todoTestDir);
    }

    public function testInteractiveKeepsTodoListWhenDenied(): void
    {
        $projectDir = $this->tempDir . '/my-project';
        $todoSrcDir = $projectDir . '/src/Capability/TodoList';

        $installer = $this->stubInstaller(function () use ($projectDir, $todoSrcDir): bool {
            mkdir($todoSrcDir, 0777, true);
            file_put_contents($projectDir . '/.env', "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return true;
        });

        $tester = new CommandTester(new NewCommand($installer));
        $tester->setInputs(['no', '', '']);
        $tester->execute(['directory' => 'my-project']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertDirectoryExists($todoSrcDir);
    }

    private function stubInstaller(\Closure $createProject): ProjectInstallerInterface
    {
        return new class ($createProject) extends ProjectInstaller {
            public function __construct(private readonly \Closure $createProject)
            {
                parent::__construct();
            }

            public function createProject(string $directory, string $repository, callable $onOutput): bool
            {
                return ($this->createProject)();
            }
        };
    }
}
