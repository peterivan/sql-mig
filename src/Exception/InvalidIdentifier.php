<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class InvalidIdentifier extends SqlMigException
{
    public function __construct(string $identifier)
    {
        parent::__construct(sprintf('Invalid PostgreSQL identifier "%s".', $identifier));
    }
}
