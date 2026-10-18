<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class PsqlUnavailable extends ConfigurationException
{
    public function __construct()
    {
        parent::__construct('psql is required on PATH.');
    }
}
