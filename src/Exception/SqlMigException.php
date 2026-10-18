<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

use RuntimeException;

abstract class SqlMigException extends RuntimeException
{
    /**
     * Process exit code for the CLI: 1 configuration, 2 validation, 3 execution.
     */
    abstract public function exitCode(): int;
}
