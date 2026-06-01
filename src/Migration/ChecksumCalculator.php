<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

final class ChecksumCalculator
{
    public const ALGORITHM = 'sha256';

    #[\NoDiscard('Migration checksums must be stored or compared to enforce immutability.')]
    public function calculate(string $content): string
    {
        return hash(self::ALGORITHM, $content);
    }
}
