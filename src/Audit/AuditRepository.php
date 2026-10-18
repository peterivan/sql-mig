<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Audit;

use PeterIvan\SqlMig\Config\Config;
use PeterIvan\SqlMig\Runner\PsqlExecutor;

final class AuditRepository
{
    public function __construct(
        private readonly AuditSchema $auditSchema,
        private readonly PsqlExecutor $psqlExecutor,
    ) {}

    public function ensureTable(Config $config): void
    {
        $this->psqlExecutor->executeSql(
            $config->databaseUrl,
            $this->auditSchema->createTableSql($config->auditSchema, $config->auditTable),
            'Audit table creation',
        );
    }

    /**
     * @return array<string, AppliedMigration>
     */
    #[\NoDiscard('Applied migrations must be used for pending and checksum validation.')]
    public function loadApplied(Config $config): array
    {
        if (!$this->tableExists($config)) {
            return [];
        }

        $target = $this->auditSchema->quotedTarget($config->auditSchema, $config->auditTable);
        $sql = <<<SQL
            select version, migration_checksum
            from {$target}
            order by version asc;
            SQL;

        $rows = $this->psqlExecutor->queryCsv($config->databaseUrl, $sql, 'Audit history query');
        $applied = [];

        foreach ($rows as $row) {
            if (count($row) < 2) {
                continue;
            }

            $applied[$row[0]] = new AppliedMigration($row[0], $row[1]);
        }

        return $applied;
    }

    /**
     * Adds the measured execution time to the audit row's metadata. Runs after
     * the migration's own transaction committed, so it is best effort.
     */
    public function recordExecutionTime(Config $config, string $version, int $executionTimeMs): void
    {
        $target = $this->auditSchema->quotedTarget($config->auditSchema, $config->auditTable);
        $sql = sprintf(
            "update %s set metadata = metadata || jsonb_build_object('executionTimeMs', %d) where version = %s;",
            $target,
            $executionTimeMs,
            $this->auditSchema->quoteLiteral($version),
        );

        $this->psqlExecutor->executeSql($config->databaseUrl, $sql, 'Audit metadata update');
    }

    public function tableExists(Config $config): bool
    {
        $sql = sprintf(
            'select exists (select 1 from information_schema.tables where table_schema = %s and table_name = %s);',
            $this->auditSchema->quoteLiteral($config->auditSchema),
            $this->auditSchema->quoteLiteral($config->auditTable),
        );

        $rows = $this->psqlExecutor->queryCsv($config->databaseUrl, $sql, 'Audit table lookup');
        $firstRow = array_first($rows);
        $value = strtolower($firstRow[0] ?? '');

        return in_array($value, ['t', 'true', '1'], true);
    }
}
