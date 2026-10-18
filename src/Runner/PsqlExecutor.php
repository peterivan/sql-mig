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

    public function executeSql(string $databaseUrl, string $sql, string $label): void
    {
        $this->run($databaseUrl, $sql, $label);
    }

    /**
     * @return list<list<string>>
     */
    #[\NoDiscard('Query output must be consumed by the caller.')]
    public function queryCsv(string $databaseUrl, string $sql, string $label): array
    {
        $output = $this->run($databaseUrl, $sql, $label, ['--csv', '--tuples-only']);
        $rows = [];

        foreach ($output as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parsed = str_getcsv($line, escape: '');
            $row = [];

            foreach ($parsed as $value) {
                $row[] = is_string($value) ? $value : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Runs psql with the SQL on stdin. The password is removed from the URL
     * and passed through PGPASSWORD so it does not appear in the process list.
     *
     * @param list<string> $extraArguments
     * @return list<string> stdout lines
     */
    private function run(string $databaseUrl, string $sql, string $label, array $extraArguments = []): array
    {
        $this->assertAvailable();

        [$connectionUrl, $password] = $this->splitPassword($databaseUrl);

        $arguments = [
            'psql',
            '--no-psqlrc',
            '--quiet',
            '--set',
            'ON_ERROR_STOP=1',
            '--dbname',
            $connectionUrl,
            ...$extraArguments,
        ];

        $environment = getenv();
        if ($password !== null) {
            $environment['PGPASSWORD'] = $password;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open($arguments, $descriptors, $pipes, null, $environment);

        $stdin = $pipes[0] ?? null;
        $stdoutPipe = $pipes[1] ?? null;
        $stderrPipe = $pipes[2] ?? null;

        if ($process === false || !is_resource($stdin) || !is_resource($stdoutPipe) || !is_resource($stderrPipe)) {
            throw new MigrationCommandFailed($label, 1, ['Unable to start psql.']);
        }

        fwrite($stdin, $sql);
        fclose($stdin);

        $stdout = stream_get_contents($stdoutPipe);
        $stderr = stream_get_contents($stderrPipe);
        fclose($stdoutPipe);
        fclose($stderrPipe);

        $exitCode = proc_close($process);
        $stdoutLines = $this->lines($stdout === false ? '' : $stdout);

        if ($exitCode !== 0) {
            throw new MigrationCommandFailed(
                $label,
                $exitCode,
                array_merge($this->lines($stderr === false ? '' : $stderr), $stdoutLines),
            );
        }

        return $stdoutLines;
    }

    /**
     * @return array{string, string|null} URL without password, decoded password
     */
    private function splitPassword(string $databaseUrl): array
    {
        $matches = [];

        if (preg_match('#^([a-z][a-z0-9+.-]*://[^:/?\#@]*):([^/?\#@]*)@#i', $databaseUrl, $matches) !== 1) {
            return [$databaseUrl, null];
        }

        $withoutPassword = $matches[1] . '@' . substr($databaseUrl, strlen($matches[0]));

        return [$withoutPassword, rawurldecode($matches[2])];
    }

    /**
     * @return list<string>
     */
    private function lines(string $text): array
    {
        $text = rtrim($text, "\n");

        return $text === '' ? [] : explode("\n", $text);
    }
}
