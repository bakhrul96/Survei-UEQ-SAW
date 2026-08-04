<?php

namespace App\Application\Reporting;

readonly class UnitProgressData
{
    public function __construct(
        public string $code,
        public string $name,
        public int $valid,
        public int $minimum,
        public int $target,
        public string $status,
    ) {}
}
