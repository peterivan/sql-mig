<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Runner;

final readonly class ApplyResult
{
    /**
     * @param string[] $appliedVersions
     */
    public function __construct(
        public array $appliedVersions,
    ) {}
}
