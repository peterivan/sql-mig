<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Exception;

final class MigrationCommandFailed extends SqlMigException
{
    /**
     * @param string[] $output
     */
    public function __construct(string $path, int $exitCode, array $output)
    {
        parent::__construct(sprintf(
            'Migration "%s" failed with exit code %d.%s',
            $path,
            $exitCode,
            $output === [] ? '' : "\n" . implode("\n", $output),
        ));
    }
}
