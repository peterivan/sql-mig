<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Audit;

use PeterIvan\SqlMig\Exception\InvalidIdentifier;

final class AuditSchema
{
    public function quotedTarget(string $schema, string $table): string
    {
        return $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier($table);
    }

    public function createTableSql(string $schema, string $table): string
    {
        $quotedSchema = $this->quoteIdentifier($schema);
        $target = $this->quotedTarget($schema, $table);

        return <<<SQL
            create schema if not exists {$quotedSchema};

            create table if not exists {$target} (
                id integer generated always as identity primary key,
                created_at timestamp with time zone not null default now(),
                version text not null,
                migration_script text not null,
                migration_checksum text not null,
                migration_content text not null,
                metadata jsonb not null default '{}',
                constraint "DatabaseVersion_version_uq" unique (version)
            );
            SQL;
    }

    public function quoteIdentifier(string $identifier): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier) !== 1) {
            throw new InvalidIdentifier($identifier);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
