<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

final class ChecksumCalculator
{
    /**
     * SHA-256 over the content with CRLF and CR line endings normalized to LF,
     * so a checkout with different line endings yields the same checksum.
     */
    public const ALGORITHM = 'sha256-lf';

    #[\NoDiscard('Migration checksums must be stored or compared to enforce immutability.')]
    public function calculate(string $content): string
    {
        return hash('sha256', str_replace(["\r\n", "\r"], "\n", $content));
    }
}
