<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MissingTransactionControl extends SqlMigException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('Migration "%s" must start with BEGIN and end with COMMIT.', $path));
    }
}
