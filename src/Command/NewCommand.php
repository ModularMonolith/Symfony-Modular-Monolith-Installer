<?php

declare(strict_types=1);

namespace ModularMonolith\Installer\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'new',
    description: 'Create a new modular monolith Symfony application',
)]
class NewCommand extends Command
{
    private const string DEFAULT_TEMPLATE = 'modular-monolith/symfony-application';

    private const string DEFAULT_REPOSITORY = 'https://github.com/ModularMonolith/Symfony-Application';

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
                default: self::DEFAULT_REPOSITORY,
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
        $io = new SymfonyStyle(
            input: $input,
            output: $output,
        );
        $filesystem = new Filesystem();
        $directory = (string) $input->getArgument('directory');
        $targetPath = realpath('.') . '/' . $directory;

        if ($filesystem->exists($targetPath)) {
            $io->error(sprintf('Directory "%s" already exists.', $targetPath));

            return Command::FAILURE;
        }

        $repository = (string) $input->getOption('repository');
        $io->title('Creating modular monolith application');
        $io->text(sprintf('Directory: %s', $targetPath));

        $createProject = $this->buildCreateProjectProcess(
            directory: $directory,
            repository: $repository,
        );
        $createProject->setTimeout(null);
        $createProject->run(function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        if (!$createProject->isSuccessful()) {
            $io->error('composer create-project failed.');

            return Command::FAILURE;
        }

        $removeExampleModule = false;
        $appSecret = bin2hex(random_bytes(16));

        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $removeExampleModule = (bool) $helper->ask(
                input: $input,
                output: $output,
                question: new ConfirmationQuestion(
                    question: 'Remove the TodoList example module? [y/N] ',
                    default: false,
                ),
            );
            $appSecretAnswer = $helper->ask(
                input: $input,
                output: $output,
                question: new Question(
                    question: 'APP_SECRET (leave empty for a generated value): ',
                    default: '',
                ),
            );
            if (is_string($appSecretAnswer) && $appSecretAnswer !== '') {
                $appSecret = $appSecretAnswer;
            }
        }

        $envPath = $targetPath . '/.env';
        if ($filesystem->exists($envPath)) {
            $envContents = (string) file_get_contents($envPath);
            $envContents = (string) preg_replace(
                pattern: '/^APP_SECRET=.*$/m',
                replacement: 'APP_SECRET=' . $appSecret,
                subject: $envContents,
            );
            $filesystem->dumpFile($envPath, $envContents);
        }

        if ($removeExampleModule) {
            $this->removeTodoListExample(
                filesystem: $filesystem,
                targetPath: $targetPath,
                io: $io,
            );
        }

        $io->success([
            'Application created successfully.',
            sprintf('Next: cd %s && make up', $directory),
        ]);

        return Command::SUCCESS;
    }

    protected function buildCreateProjectProcess(string $directory, string $repository): Process
    {
        $command = [
            'composer',
            'create-project',
            self::DEFAULT_TEMPLATE,
            $directory,
            '--repository=' . json_encode(
                value: [
                    'type' => 'vcs',
                    'url' => $repository,
                ],
                flags: JSON_THROW_ON_ERROR,
            ),
        ];

        return new Process(command: $command);
    }

    private function removeTodoListExample(
        Filesystem $filesystem,
        string $targetPath,
        SymfonyStyle $io,
    ): void {
        $paths = [
            $targetPath . '/src/Capability/TodoList',
            $targetPath . '/tests/Capability/TodoList',
        ];

        foreach ($paths as $path) {
            if ($filesystem->exists($path)) {
                $filesystem->remove($path);
            }
        }

        $io->note('Removed TodoList example module from src/ and tests/. Review routes, fixtures, and migrations if needed.');
    }
}
