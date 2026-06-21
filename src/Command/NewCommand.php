<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Command;

use ModularMonolith\Installer\Installer\ProjectInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'new',
    description: 'Create a new modular monolith Symfony application',
)]
class NewCommand extends Command
{
    private ProjectInstaller $installer;

    public function __construct(?ProjectInstaller $installer = null)
    {
        parent::__construct();
        $this->installer = $installer ?? new ProjectInstaller();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'directory',
                mode: InputArgument::REQUIRED,
                description: 'Target directory for the new project',
            )
            ->addOption(
                name: 'repository',
                mode: InputOption::VALUE_REQUIRED,
                description: 'VCS URL when the template is not on Packagist',
                default: ProjectInstaller::DEFAULT_REPOSITORY,
            )
            ->addOption(
                name: 'no-interaction',
                shortcut: 'n',
                mode: InputOption::VALUE_NONE,
                description: 'Skip interactive prompts',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle(input: $input, output: $output);
        $directory = (string) $input->getArgument('directory');
        $targetPath = realpath('.') . '/' . $directory;

        if (is_dir($targetPath)) {
            $io->error(sprintf('Directory "%s" already exists.', $targetPath));

            return Command::FAILURE;
        }

        $repository = (string) $input->getOption('repository');
        $io->title('Creating modular monolith application');
        $io->text(sprintf('Directory: %s', $targetPath));

        $success = $this->installer->createProject(
            directory: $directory,
            repository: $repository,
            onOutput: fn (string $type, string $buffer) => $output->write($buffer),
        );

        if (!$success) {
            $io->error('composer create-project failed.');

            return Command::FAILURE;
        }

        $appSecret = bin2hex(random_bytes(16));
        $removeExampleModule = false;

        if (!$input->getOption('no-interaction')) {
            $removeExampleModule = $io->confirm('Remove the TodoList example module?', false);
            $appSecretAnswer = $io->ask('APP_SECRET (leave empty for a generated value)');
            if (is_string($appSecretAnswer) && $appSecretAnswer !== '') {
                $appSecret = $appSecretAnswer;
            }
        }

        $this->installer->writeAppSecret(targetPath: $targetPath, appSecret: $appSecret);

        if ($removeExampleModule) {
            $this->installer->removeTodoListExample(targetPath: $targetPath);
            $io->note([
                'Removed TodoList example module from src/ and tests/.',
                'Review routes, fixtures, and migrations if needed.',
            ]);
        }

        $io->success([
            'Application created successfully.',
            sprintf('Next: cd %s && make up', $directory),
        ]);

        return Command::SUCCESS;
    }
}
