<?php

namespace App\Application\Survey;

use Carbon\CarbonImmutable;

readonly class SubmitSurveyData
{
    /** @param array<int, int> $answers */
    public function __construct(
        public int $periodId,
        public int $respondentId,
        public string $sessionId,
        public int $unitId,
        public string $idempotencyKey,
        public string $instrumentVersion,
        public CarbonImmutable $startedAt,
        public array $answers,
    ) {}
}
