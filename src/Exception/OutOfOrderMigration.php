<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class OutOfOrderMigration extends SqlMigException
{
    public function __construct(string $version, string $latestVersion)
    {
        parent::__construct(sprintf(
            'Local migration "%s" is older than or equal to latest applied migration "%s" and is not in audit history.',
            $version,
            $latestVersion,
        ));
    }
}
