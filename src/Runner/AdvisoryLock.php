<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

use PeterIvan\SqlMig\Config\Config;

final class AdvisoryLock
{
    public const DEFAULT_LOCK_KEY = 642039221;

    public function __construct(
        private readonly PsqlExecutor $psqlExecutor,
    ) {}

    public function acquire(Config $config): void
    {
        $this->psqlExecutor->executeSql(
            $config->databaseUrl,
            'select pg_advisory_lock(' . self::DEFAULT_LOCK_KEY . ');',
        );
    }

    public function release(Config $config): void
    {
        $this->psqlExecutor->executeSql(
            $config->databaseUrl,
            'select pg_advisory_unlock(' . self::DEFAULT_LOCK_KEY . ');',
        );
    }
}
