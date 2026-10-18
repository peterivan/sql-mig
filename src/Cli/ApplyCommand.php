<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Cli;

use PeterIvan\SqlMig\Config\ConfigResolver;
use PeterIvan\SqlMig\Exception\SqlMigException;
use PeterIvan\SqlMig\Runner\MigrationRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplyCommand extends Command
{
    use ConfigOptions;

    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly MigrationRunner $migrationRunner,
    ) {
        parent::__construct('apply');
    }

    protected function configure(): void
    {
        $this->setDescription('Apply pending database migrations.');
        $this->addConfigOptions($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->migrationRunner->apply($this->configResolver->resolve(
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

        if ($result->appliedVersions === []) {
            $output->writeln('<info>No pending migrations.</info>');

            return self::SUCCESS;
        }

        $output->writeln(sprintf('<info>Applied %d migration(s).</info>', count($result->appliedVersions)));

        foreach ($result->appliedVersions as $version) {
            $output->writeln(' - ' . $version);
        }

        return self::SUCCESS;
    }
}
