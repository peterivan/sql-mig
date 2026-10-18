<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

use PeterIvan\SqlMig\Audit\AuditRepository;
use PeterIvan\SqlMig\Config\Config;
use PeterIvan\SqlMig\Exception\MigrationChecksumMismatch;
use PeterIvan\SqlMig\Exception\OutOfOrderMigration;
use PeterIvan\SqlMig\Migration\AuditSqlInjector;
use PeterIvan\SqlMig\Migration\MigrationDiscovery;
use PeterIvan\SqlMig\Migration\MigrationFile;
use PeterIvan\SqlMig\Migration\TransactionBoundaryValidator;

final class MigrationRunner
{
    public const TOOL_VERSION = '1.0.0-dev';

    public function __construct(
        private readonly MigrationDiscovery $migrationDiscovery,
        private readonly TransactionBoundaryValidator $transactionBoundaryValidator,
        private readonly AuditRepository $auditRepository,
        private readonly AuditSqlInjector $auditSqlInjector,
        private readonly PsqlExecutor $psqlExecutor,
    ) {}

    #[\NoDiscard('Validation result must be reported or used before applying migrations.')]
    public function validate(Config $config): ValidationResult
    {
        $migrations = $this->migrationDiscovery->discover($config->migrationsPath);

        foreach ($migrations as $migration) {
            $this->transactionBoundaryValidator->assertValid($migration);
        }

        $applied = $this->auditRepository->loadApplied($config);
        $latestVersion = $this->latestAppliedVersion(array_keys($applied));
        $pending = [];

        foreach ($migrations as $migration) {
            $version = $migration->version->value;

            if (isset($applied[$version])) {
                if ($applied[$version]->checksum !== $migration->checksum) {
                    throw new MigrationChecksumMismatch($version);
                }

                continue;
            }

            if ($latestVersion !== null && $version <= $latestVersion) {
                throw new OutOfOrderMigration($version, $latestVersion);
            }

            $pending[] = $version;
        }

        return new ValidationResult($pending);
    }

    #[\NoDiscard('Apply result must be reported to the caller.')]
    public function apply(Config $config): ApplyResult
    {
        $this->auditRepository->ensureTable($config);

        $validationResult = $this->validate($config);
        $pendingByVersion = array_flip($validationResult->pendingVersions);
        $migrations = array_values(array_filter(
            $this->migrationDiscovery->discover($config->migrationsPath),
            static fn(MigrationFile $migration): bool => isset($pendingByVersion[$migration->version->value]),
        ));
        $applied = [];

        foreach ($migrations as $migration) {
            $script = $this->auditSqlInjector->inject(
                $migration,
                $config->auditSchema,
                $config->auditTable,
                0,
                self::TOOL_VERSION,
            );

            $this->psqlExecutor->executeSql($config->databaseUrl, $script);
            $applied[] = $migration->version->value;
        }

        return new ApplyResult($applied);
    }

    /**
     * @param string[] $versions
     */
    private function latestAppliedVersion(array $versions): ?string
    {
        if ($versions === []) {
            return null;
        }

        rsort($versions, SORT_STRING);

        return $versions[0];
    }
}
