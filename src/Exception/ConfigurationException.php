<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

abstract class ConfigurationException extends SqlMigException
{
    public const EXIT_CODE = 1;

    public function exitCode(): int
    {
        return self::EXIT_CODE;
    }
}
