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
        $constraint = $this->quoteIdentifier($table . '_version_uq');

        return <<<SQL
            create schema if not exists {$quotedSchema};

            create table if not exists {$target} (
                id integer generated always as identity primary key,
                created_at timestamp with time zone not null default now(),
                version text collate "C" not null,
                migration_script text not null,
                migration_checksum text not null,
                migration_content text not null,
                metadata jsonb not null default '{}',
                constraint {$constraint} unique (version)
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

    public function quoteLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Dollar-quotes a value with a tag that does not occur in it, so the
     * content needs no escaping regardless of server string settings.
     */
    public function quoteDollar(string $value): string
    {
        $tag = '$sqlmig$';
        $suffix = 0;

        while (str_contains($value, $tag)) {
            $suffix++;
            $tag = '$sqlmig' . $suffix . '$';
        }

        return $tag . $value . $tag;
    }
}
