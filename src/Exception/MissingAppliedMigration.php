<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MissingAppliedMigration extends ValidationException
{
    public function __construct(string $version)
    {
        parent::__construct(sprintf('Applied migration "%s" has no local migration file.', $version));
    }
}
