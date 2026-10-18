<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

abstract class ExecutionException extends SqlMigException
{
    public const EXIT_CODE = 3;

    public function exitCode(): int
    {
        return self::EXIT_CODE;
    }
}
