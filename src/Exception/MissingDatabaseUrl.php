<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MissingDatabaseUrl extends SqlMigException
{
    public function __construct()
    {
        parent::__construct('Missing database URL. Pass --database-url or set DATABASE_URL.');
    }
}
