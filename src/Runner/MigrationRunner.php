<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

use PeterIvan\SqlMig\Audit\AuditRepository;
use PeterIvan\SqlMig\Config\Config;
use PeterIvan\SqlMig\Exception\MigrationChecksumMismatch;
use PeterIvan\SqlMig\Exception\MigrationCommandFailed;
use PeterIvan\SqlMig\Exception\MissingAppliedMigration;
use PeterIvan\SqlMig\Exception\OutOfOrderMigration;
use PeterIvan\SqlMig\Migration\AuditSqlInjector;
use PeterIvan\SqlMig\Migration\MigrationDiscovery;
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
        $localVersions = [];
        $pending = [];

        foreach ($migrations as $migration) {
            $version = $migration->version->value;
            $localVersions[$version] = true;

            if (isset($applied[$version])) {
                if ($applied[$version]->checksum !== $migration->checksum) {
                    throw new MigrationChecksumMismatch($version);
                }

                continue;
            }

            if ($latestVersion !== null && $version <= $latestVersion) {
                throw new OutOfOrderMigration($version, $latestVersion);
            }

            $pending[] = $migration;
        }

        foreach (array_keys($applied) as $appliedVersion) {
            if (!isset($localVersions[$appliedVersion])) {
                throw new MissingAppliedMigration($appliedVersion);
            }
        }

        return new ValidationResult($pending);
    }

    #[\NoDiscard('Apply result must be reported to the caller.')]
    public function apply(Config $config): ApplyResult
    {
        $validationResult = $this->validate($config);

        if ($validationResult->pendingMigrations === []) {
            return new ApplyResult([]);
        }

        $this->auditRepository->ensureTable($config);
        $applied = [];

        foreach ($validationResult->pendingMigrations as $migration) {
            $script = $this->auditSqlInjector->inject(
                $migration,
                $config->auditSchema,
                $config->auditTable,
                self::TOOL_VERSION,
            );

            $startedAt = microtime(true);
            $this->psqlExecutor->executeSql($config->databaseUrl, $script, sprintf('Migration "%s"', $migration->path));
            $executionTimeMs = (int) round((microtime(true) - $startedAt) * 1000);
            $applied[] = $migration->version->value;

            try {
                $this->auditRepository->recordExecutionTime($config, $migration->version->value, $executionTimeMs);
            } catch (MigrationCommandFailed) {
                // The migration is committed; a lost timing value must not fail the run.
            }
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
