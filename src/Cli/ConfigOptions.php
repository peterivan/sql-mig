<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait ConfigOptions
{
    private function addConfigOptions(Command $command): void
    {
        $command
            ->addOption(
                'database-url',
                null,
                InputOption::VALUE_REQUIRED,
                'PostgreSQL connection URL. Falls back to DATABASE_URL.',
            )
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Migration directory path.')
            ->addOption(
                'audit-schema',
                null,
                InputOption::VALUE_REQUIRED,
                'Audit schema. Falls back to SQL_MIG_AUDIT_SCHEMA.',
            )
            ->addOption(
                'audit-table',
                null,
                InputOption::VALUE_REQUIRED,
                'Audit table. Falls back to SQL_MIG_AUDIT_TABLE.',
            );
    }

    private function stringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);

        return is_string($value) ? $value : null;
    }
}
