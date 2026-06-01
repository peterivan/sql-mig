<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

use PeterIvan\SqlMig\Exception\MigrationCommandFailed;
use PeterIvan\SqlMig\Exception\PsqlUnavailable;

final class PsqlExecutor
{
    public function assertAvailable(): void
    {
        $output = [];
        $exitCode = 0;

        exec('command -v psql 2>/dev/null', $output, $exitCode);

        if ($exitCode !== 0 || $output === []) {
            throw new PsqlUnavailable();
        }
    }

    public function executeSql(string $databaseUrl, string $sql): void
    {
        $this->run($databaseUrl, $sql);
    }

    /**
     * @return list<list<string>>
     */
    #[\NoDiscard('Query output must be consumed by the caller.')]
    public function queryCsv(string $databaseUrl, string $sql): array
    {
        $output = $this->run($databaseUrl, $sql, ['--csv', '--tuples-only']);
        $rows = [];

        foreach ($output as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parsed = str_getcsv($line);
            $row = [];

            foreach ($parsed as $value) {
                $row[] = is_string($value) ? $value : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param string[] $extraArguments
     * @return string[]
     */
    private function run(string $databaseUrl, string $sql, array $extraArguments = []): array
    {
        $this->assertAvailable();

        $handle = tmpfile();
        if ($handle === false) {
            throw new MigrationCommandFailed('temporary SQL file', 1, ['Unable to create temporary file.']);
        }

        fwrite($handle, $sql);
        $metadata = stream_get_meta_data($handle);
        $path = $metadata['uri'] ?? null;

        if (!is_string($path)) {
            fclose($handle);

            throw new MigrationCommandFailed('temporary SQL file', 1, ['Unable to resolve temporary file path.']);
        }

        $arguments = array_merge([
            'psql',
            '--set',
            'ON_ERROR_STOP=1',
            '--dbname',
            $databaseUrl,
            '--file',
            $path,
        ], $extraArguments);
        $command = implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1';

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);
        fclose($handle);

        if ($exitCode !== 0) {
            throw new MigrationCommandFailed($path, $exitCode, $output);
        }

        return $output;
    }
}
