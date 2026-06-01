<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class DuplicateMigrationVersion extends SqlMigException
{
    /**
     * @param string[] $paths
     */
    public function __construct(string $version, array $paths)
    {
        parent::__construct(sprintf('Duplicate migration version "%s": %s.', $version, implode(', ', $paths)));
    }
}
