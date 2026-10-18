<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MigrationCommandFailed extends ExecutionException
{
    /**
     * @param string[] $output
     */
    public function __construct(string $label, int $exitCode, array $output)
    {
        parent::__construct(sprintf(
            '%s failed with exit code %d.%s',
            $label,
            $exitCode,
            $output === [] ? '' : "\n" . implode("\n", $output),
        ));
    }
}
