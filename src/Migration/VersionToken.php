<?php

declare(strict_types=1);

namespace PeterIvan\SqlMig\Migration;

final readonly class VersionToken
{
    public function __construct(
        public string $value,
        public string $date,
        public string $time,
        public int $sequence,
    ) {}

    public function compare(self $other): int
    {
        return [$this->date, $this->time, $this->sequence] <=> [$other->date, $other->time, $other->sequence];
    }
}
