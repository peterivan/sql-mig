<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

use DateTimeImmutable;
use PeterIvan\SqlMig\Exception\InvalidMigrationFilename;

final class VersionTokenParser
{
    private const PATTERN = '/^(?<date>\d{4}-\d{2}-\d{2})_(?<time>[0-2]\d[0-5]\d)(?:_(?<sequence>\d{2}))?_[a-z0-9][a-z0-9_]*\.sql$/';

    #[\NoDiscard('Parsed migration version token must be used for ordering and identity.')]
    public function parseFilename(string $filename): VersionToken
    {
        $matches = [];

        if (preg_match(self::PATTERN, $filename, $matches) !== 1) {
            throw new InvalidMigrationFilename($filename);
        }

        if (!$this->isRealDate($matches['date']) || (int) substr($matches['time'], 0, 2) > 23) {
            throw new InvalidMigrationFilename($filename);
        }

        $sequenceMatch = $matches['sequence'] ?? '';

        if ($sequenceMatch === '00') {
            throw new InvalidMigrationFilename($filename);
        }

        $sequence = $sequenceMatch !== '' ? (int) $sequenceMatch : 0;

        $value = $matches['date'] . '_' . $matches['time'];
        if ($sequence > 0) {
            $value .= '_' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
        }

        return new VersionToken($value, $matches['date'], $matches['time'], $sequence);
    }

    private function isRealDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }
}
