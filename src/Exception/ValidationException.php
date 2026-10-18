<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

abstract class ValidationException extends SqlMigException
{
    public const EXIT_CODE = 2;

    public function exitCode(): int
    {
        return self::EXIT_CODE;
    }
}
