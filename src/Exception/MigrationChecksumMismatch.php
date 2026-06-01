<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MigrationChecksumMismatch extends SqlMigException
{
    public function __construct(string $version)
    {
        parent::__construct(sprintf('Applied migration "%s" has a different local checksum.', $version));
    }
}
