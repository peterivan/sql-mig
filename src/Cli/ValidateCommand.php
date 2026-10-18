<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Cli;

use PeterIvan\SqlMig\Config\ConfigResolver;
use PeterIvan\SqlMig\Exception\SqlMigException;
use PeterIvan\SqlMig\Runner\MigrationRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateCommand extends Command
{
    use ConfigOptions;

    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly MigrationRunner $migrationRunner,
    ) {
        parent::__construct('validate');
    }

    protected function configure(): void
    {
        $this->setDescription('Validate migration files and audit history without applying migrations.');
        $this->addConfigOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->migrationRunner->validate($this->configResolver->resolve(
                [
                    'database-url' => $this->stringOption($input, 'database-url'),
                    'path' => $this->stringOption($input, 'path'),
                    'audit-schema' => $this->stringOption($input, 'audit-schema'),
                    'audit-table' => $this->stringOption($input, 'audit-table'),
                ],
                getcwd() ?: '.',
            ));
        } catch (SqlMigException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return self::FAILURE;
        }

        $output->writeln('<info>Validation passed.</info>');
        $output->writeln(sprintf('Pending migrations: %d', count($result->pendingVersions)));

        foreach ($result->pendingVersions as $version) {
            $output->writeln(' - ' . $version);
        }

        return self::SUCCESS;
    }
}
