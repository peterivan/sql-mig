<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

use PeterIvan\SqlMig\Migration\MigrationFile;

final readonly class ValidationResult
{
    /**
     * @var string[]
     */
    public array $pendingVersions;

    /**
     * @param MigrationFile[] $pendingMigrations
     */
    public function __construct(
        public array $pendingMigrations,
    ) {
        $this->pendingVersions = array_map(
            static fn(MigrationFile $migration): string => $migration->version->value,
            $pendingMigrations,
        );
    }
}
