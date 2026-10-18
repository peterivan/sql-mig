<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class InvalidMigrationFilename extends SqlMigException
{
    public function __construct(string $filename)
    {
        parent::__construct(sprintf('Invalid migration filename "%s".', $filename));
    }
}
