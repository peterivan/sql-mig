<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

final readonly class MigrationFile
{
    public function __construct(
        public string $path,
        public string $filename,
        public VersionToken $version,
        public string $content,
        public string $checksum,
    ) {}
}
