<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

use PeterIvan\SqlMig\Exception\MissingTransactionControl;

final class TransactionBoundaryValidator
{
    public function assertValid(MigrationFile $migration): void
    {
        $statements = $this->topLevelStatements($migration->content);
        $first = strtolower($statements[0] ?? '');
        $last = strtolower($statements[count($statements) - 1] ?? '');

        if ($first !== 'begin' || $last !== 'commit') {
            throw new MissingTransactionControl($migration->path);
        }

        $innerTransactionBoundaries = array_intersect(
            array_map('strtolower', array_slice($statements, 1, -1)),
            ['begin', 'commit'],
        );

        if ($innerTransactionBoundaries !== []) {
            throw new MissingTransactionControl($migration->path);
        }
    }

    public function finalTopLevelStatementStart(string $sql): ?int
    {
        $statements = $this->topLevelStatementSpans($sql);
        $last = array_last($statements);

        return $last['start'] ?? null;
    }

    /**
     * @return string[]
     */
    #[\NoDiscard('Parsed top-level statements must be inspected by validation or SQL injection.')]
    public function topLevelStatements(string $sql): array
    {
        return array_map(
            static fn(array $statement): string => $statement['text'],
            $this->topLevelStatementSpans($sql),
        );
    }

    /**
     * @return list<array{text: string, start: int, end: int}>
     */
    private function topLevelStatementSpans(string $sql): array
    {
        $statements = [];
        $statement = '';
        $statementStart = null;
        $length = strlen($sql);
        $dollarQuote = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($dollarQuote !== null) {
                if (substr($sql, $i, strlen($dollarQuote)) === $dollarQuote) {
                    $i += strlen($dollarQuote) - 1;
                    $dollarQuote = null;
                }
                continue;
            }

            if ($char === '-' && $next === '-') {
                $i = $this->skipUntil($sql, $i + 2, "\n");
                continue;
            }

            if ($char === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $length : $end + 1;
                continue;
            }

            if ($char === '\'' || $char === '"') {
                $statementStart ??= $i;
                $i = $this->skipQuoted($sql, $i, $char);
                continue;
            }

            $matches = [];
            if ($char === '$' && preg_match('/\G\$[A-Za-z_][A-Za-z0-9_]*\$|\G\$\$/', $sql, $matches, 0, $i) === 1) {
                $statementStart ??= $i;
                $dollarQuote = $matches[0];
                $i += strlen($dollarQuote) - 1;
                continue;
            }

            if ($char === ';') {
                $normalized = trim(preg_replace('/\s+/', ' ', $statement) ?? '');
                if ($normalized !== '' && $statementStart !== null) {
                    $statements[] = [
                        'text' => $normalized,
                        'start' => $statementStart,
                        'end' => $i,
                    ];
                }
                $statement = '';
                $statementStart = null;
                continue;
            }

            if (!ctype_space($char)) {
                $statementStart ??= $i;
            }

            $statement .= $char;
        }

        return $statements;
    }

    private function skipUntil(string $sql, int $offset, string $needle): int
    {
        $position = strpos($sql, $needle, $offset);

        return $position === false ? strlen($sql) : $position;
    }

    private function skipQuoted(string $sql, int $offset, string $quote): int
    {
        $length = strlen($sql);

        for ($i = $offset + 1; $i < $length; $i++) {
            if ($sql[$i] !== $quote) {
                continue;
            }

            if (($sql[$i + 1] ?? '') === $quote) {
                $i++;
                continue;
            }

            return $i;
        }

        return $length;
    }
}
