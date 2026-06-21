<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Tests\Installer;

use ModularMonolith\Installer\Installer\ProjectInstaller;
use ModularMonolith\Installer\Tests\InstallerTestCase;

final class ProjectInstallerTest extends InstallerTestCase
{
    private string $tempDir;
    private ProjectInstaller $installer;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/modulith-installer-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->installer = new ProjectInstaller();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testComputeContainerPrefixLowercasesInput(): void
    {
        self::assertSame('myproject', ProjectInstaller::computeContainerPrefix('MyProject'));
    }

    public function testComputeContainerPrefixReplacesSpecialCharsWithHyphen(): void
    {
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my_project'));
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my project'));
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my.project'));
    }

    public function testComputeContainerPrefixCollapsesConsecutiveSpecialChars(): void
    {
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my--project'));
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my__project'));
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my_ -project'));
    }

    public function testComputeContainerPrefixTrimsLeadingAndTrailingHyphens(): void
    {
        self::assertSame('project', ProjectInstaller::computeContainerPrefix('-project-'));
        self::assertSame('project', ProjectInstaller::computeContainerPrefix('_project_'));
    }

    public function testComputeContainerPrefixHandlesAlreadyValidInput(): void
    {
        self::assertSame('my-project', ProjectInstaller::computeContainerPrefix('my-project'));
        self::assertSame('myproject', ProjectInstaller::computeContainerPrefix('myproject'));
    }

    public function testWriteAppSecretReplacesExistingKey(): void
    {
        $envPath = $this->tempDir . '/.env';
        file_put_contents($envPath, "APP_ENV=dev\nAPP_SECRET=old-secret\n");

        $this->installer->writeAppSecret($this->tempDir, 'new-secret');

        $contents = (string) file_get_contents($envPath);
        self::assertStringContainsString('APP_SECRET=new-secret', $contents);
        self::assertStringNotContainsString('APP_SECRET=old-secret', $contents);
    }

    public function testWriteAppSecretAppendsWhenKeyMissing(): void
    {
        $envPath = $this->tempDir . '/.env';
        file_put_contents($envPath, "APP_ENV=dev\n");

        $this->installer->writeAppSecret($this->tempDir, 'new-secret');

        $contents = (string) file_get_contents($envPath);
        self::assertStringContainsString('APP_SECRET=new-secret', $contents);
        self::assertStringContainsString('APP_ENV=dev', $contents);
    }

    public function testWriteAppSecretDoesNothingWhenEnvFileMissing(): void
    {
        self::expectNotToPerformAssertions();
        $this->installer->writeAppSecret($this->tempDir . '/nonexistent', 'secret');
    }

    public function testWriteComposeProjectNameSanitizesAndWritesKey(): void
    {
        $envPath = $this->tempDir . '/.env';
        file_put_contents($envPath, "APP_ENV=dev\n");

        $this->installer->writeComposeProjectName($this->tempDir, 'My Project!');

        self::assertMatchesRegularExpression(
            '/^COMPOSE_PROJECT_NAME=my-project$/m',
            (string) file_get_contents($envPath),
        );
    }

    public function testWriteComposeProjectNameReplacesExistingKey(): void
    {
        $envPath = $this->tempDir . '/.env';
        file_put_contents($envPath, "COMPOSE_PROJECT_NAME=old-name\n");

        $this->installer->writeComposeProjectName($this->tempDir, 'new-name');

        $contents = (string) file_get_contents($envPath);
        self::assertStringContainsString('COMPOSE_PROJECT_NAME=new-name', $contents);
        self::assertStringNotContainsString('COMPOSE_PROJECT_NAME=old-name', $contents);
    }

    public function testWriteComposeProjectNameDoesNothingWhenEnvFileMissing(): void
    {
        self::expectNotToPerformAssertions();
        $this->installer->writeComposeProjectName($this->tempDir . '/nonexistent', 'prefix');
    }

    public function testRemoveTodoListExampleRemovesExistingDirectories(): void
    {
        $srcDir = $this->tempDir . '/src/Capability/TodoList';
        $testDir = $this->tempDir . '/tests/Capability/TodoList';
        mkdir($srcDir, 0777, true);
        mkdir($testDir, 0777, true);

        $this->installer->removeTodoListExample($this->tempDir);

        self::assertDirectoryDoesNotExist($srcDir);
        self::assertDirectoryDoesNotExist($testDir);
    }

    public function testRemoveTodoListExampleSkipsMissingDirectories(): void
    {
        self::expectNotToPerformAssertions();
        $this->installer->removeTodoListExample($this->tempDir);
    }

    public function testRemoveTodoListExampleRemovesOnlyPresentDirectory(): void
    {
        $srcDir = $this->tempDir . '/src/Capability/TodoList';
        mkdir($srcDir, 0777, true);

        $this->installer->removeTodoListExample($this->tempDir);

        self::assertDirectoryDoesNotExist($srcDir);
    }
}
