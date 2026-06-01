<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Config;

final readonly class Config
{
    public function __construct(
        public string $databaseUrl,
        public string $migrationsPath,
        public string $auditSchema,
        public string $auditTable,
    ) {}
}
