<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Audit;

final readonly class AppliedMigration
{
    public function __construct(
        public string $version,
        public string $checksum,
    ) {}
}
