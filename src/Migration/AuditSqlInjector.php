<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

use PeterIvan\SqlMig\Audit\AuditSchema;
use PeterIvan\SqlMig\Exception\MissingTransactionControl;

final class AuditSqlInjector
{
    public function __construct(
        private readonly AuditSchema $auditSchema,
        private readonly TransactionBoundaryValidator $transactionBoundaryValidator,
    ) {}

    #[\NoDiscard('Injected migration SQL must be executed instead of the original content.')]
    public function inject(
        MigrationFile $migration,
        string $schema,
        string $table,
        int $executionTimeMs,
        string $toolVersion,
    ): string {
        $target = $this->auditSchema->quotedTarget($schema, $table);
        $content = rtrim($migration->content);
        $commitPosition = $this->transactionBoundaryValidator->finalTopLevelStatementStart($content);

        if ($commitPosition === null) {
            throw new MissingTransactionControl($migration->path);
        }

        $beforeCommit = rtrim(substr($content, 0, $commitPosition));
        $commit = substr($content, $commitPosition);
        $metadata = json_encode([
            'auditSchemaVersion' => 1,
            'toolVersion' => $toolVersion,
            'executionTimeMs' => $executionTimeMs,
            'checksumAlgorithm' => ChecksumCalculator::ALGORITHM,
        ], JSON_THROW_ON_ERROR);

        $insert = sprintf(
            "insert into %s (version, migration_script, migration_checksum, migration_content, metadata)\nvalues (%s, %s, %s, %s, %s::jsonb);",
            $target,
            $this->quoteLiteral($migration->version->value),
            $this->quoteLiteral($migration->filename),
            $this->quoteLiteral($migration->checksum),
            $this->quoteLiteral($migration->content),
            $this->quoteLiteral($metadata),
        );

        return $beforeCommit . "\n\n" . $insert . "\n\n" . $commit . "\n";
    }

    private function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
