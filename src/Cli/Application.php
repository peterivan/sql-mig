<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Cli;

use PeterIvan\SqlMig\Audit\AuditRepository;
use PeterIvan\SqlMig\Audit\AuditSchema;
use PeterIvan\SqlMig\Config\ConfigResolver;
use PeterIvan\SqlMig\Migration\AuditSqlInjector;
use PeterIvan\SqlMig\Migration\ChecksumCalculator;
use PeterIvan\SqlMig\Migration\MigrationDiscovery;
use PeterIvan\SqlMig\Migration\TransactionBoundaryValidator;
use PeterIvan\SqlMig\Migration\VersionTokenParser;
use PeterIvan\SqlMig\Runner\MigrationRunner;
use PeterIvan\SqlMig\Runner\PsqlExecutor;
use Symfony\Component\Console\Application as ConsoleApplication;

final class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct('sql-mig', MigrationRunner::TOOL_VERSION);

        $auditSchema = new AuditSchema();
        $psqlExecutor = new PsqlExecutor();
        $transactionBoundaryValidator = new TransactionBoundaryValidator();
        $runner = new MigrationRunner(
            new MigrationDiscovery(new VersionTokenParser(), new ChecksumCalculator()),
            $transactionBoundaryValidator,
            new AuditRepository($auditSchema, $psqlExecutor),
            new AuditSqlInjector($auditSchema, $transactionBoundaryValidator),
            $psqlExecutor,
        );
        $configResolver = new ConfigResolver();

        $this->addCommand(new ValidateCommand($configResolver, $runner));
        $this->addCommand(new ApplyCommand($configResolver, $runner));
        $this->setDefaultCommand('list');
    }
}
