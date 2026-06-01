<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

final readonly class ValidationResult
{
    /**
     * @param string[] $pendingVersions
     */
    public function __construct(
        public array $pendingVersions,
    ) {}
}
