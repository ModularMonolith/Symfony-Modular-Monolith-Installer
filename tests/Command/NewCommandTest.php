<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Tests\Command;

use ModularMonolith\Installer\Command\NewCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

final class NewCommandTest extends TestCase
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

        $tester = new CommandTester($this->buildCommandWithStub(fn () => $this->buildSuccessProcess()));
        $tester->execute(['directory' => 'already-exists']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testSucceedsInNoInteractionModeWhenProcessSucceeds(): void
    {
        $tester = new CommandTester($this->buildCommandWithStub(function (): Process {
            mkdir($this->tempDir . '/new-project', 0777, true);
            file_put_contents($this->tempDir . '/new-project/.env', "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return $this->buildSuccessProcess();
        }));

        $tester->execute(['directory' => 'new-project', '--no-interaction' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Application created successfully', $tester->getDisplay());
    }

    public function testEnvSecretIsReplacedOnSuccess(): void
    {
        $envFile = $this->tempDir . '/secret-project/.env';

        $tester = new CommandTester($this->buildCommandWithStub(function () use ($envFile): Process {
            mkdir(dirname($envFile), 0777, true);
            file_put_contents($envFile, "APP_ENV=dev\nAPP_SECRET=changeme\n");

            return $this->buildSuccessProcess();
        }));

        $tester->execute(['directory' => 'secret-project', '--no-interaction' => true]);

        $envContents = (string) file_get_contents($envFile);
        self::assertStringNotContainsString('APP_SECRET=changeme', $envContents);
        self::assertMatchesRegularExpression('/^APP_SECRET=[a-f0-9]{32}$/m', $envContents);
    }

    public function testFailsWhenProcessFails(): void
    {
        $tester = new CommandTester($this->buildCommandWithStub(fn () => $this->buildFailProcess()));
        $tester->execute(['directory' => 'failed-project', '--no-interaction' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('composer create-project failed', $tester->getDisplay());
    }

    private function buildCommandWithStub(\Closure $processFactory): NewCommand
    {
        return new class($processFactory) extends NewCommand {
            public function __construct(private readonly \Closure $processFactory)
            {
                parent::__construct();
            }

            protected function buildCreateProjectProcess(string $directory, string $repository): Process
            {
                return ($this->processFactory)();
            }
        };
    }

    private function buildSuccessProcess(): Process
    {
        $process = $this->createMock(Process::class);
        $process->method('setTimeout')->willReturnSelf();
        $process->method('run')->willReturn(0);
        $process->method('isSuccessful')->willReturn(true);

        return $process;
    }

    private function buildFailProcess(): Process
    {
        $process = $this->createMock(Process::class);
        $process->method('setTimeout')->willReturnSelf();
        $process->method('run')->willReturn(1);
        $process->method('isSuccessful')->willReturn(false);

        return $process;
    }

    private function removeDir(string $path): void
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
