<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Config;

use PeterIvan\SqlMig\Exception\MissingDatabaseUrl;

final class ConfigResolver
{
    public function __construct(
        private readonly DatabaseUrlNormalizer $databaseUrlNormalizer = new DatabaseUrlNormalizer(),
    ) {}

    /**
     * @param array{
     *     database-url?: string|null,
     *     path?: string|null,
     *     audit-schema?: string|null,
     *     audit-table?: string|null
     * } $options
     */
    #[\NoDiscard('Resolved configuration must be passed to a runner or command handler.')]
    public function resolve(array $options, string $workingDirectory): Config
    {
        $databaseUrl = $this->firstNonEmpty($options['database-url'] ?? null, getenv('DATABASE_URL') ?: null);

        if ($databaseUrl === null) {
            throw new MissingDatabaseUrl();
        }

        $databaseUrl = $this->databaseUrlNormalizer->normalize($databaseUrl);

        $path = $this->firstNonEmpty($options['path'] ?? null) ?? 'database/migrations';
        $auditSchema =
            $this->firstNonEmpty($options['audit-schema'] ?? null, getenv('SQL_MIG_AUDIT_SCHEMA') ?: null) ?? 'Audit';
        $auditTable =
            $this->firstNonEmpty($options['audit-table'] ?? null, getenv('SQL_MIG_AUDIT_TABLE') ?: null)
            ?? 'DatabaseVersion';

        return new Config($databaseUrl, $this->absolutePath($path, $workingDirectory), $auditSchema, $auditTable);
    }

    private function absolutePath(string $path, string $workingDirectory): string
    {
        if ($path !== '' && $path[0] === '/') {
            return $path;
        }

        return rtrim($workingDirectory, '/') . '/' . $path;
    }

    private function firstNonEmpty(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
