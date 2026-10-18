<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MigrationDirectoryNotFound extends ConfigurationException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Migration directory "%s" does not exist or is not readable.', $path));
    }
}
