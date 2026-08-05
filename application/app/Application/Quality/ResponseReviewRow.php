<?php

namespace App\Application\Quality;

use Carbon\CarbonInterface;

final readonly class ResponseReviewRow
{
    /** @param array{fast_completion: bool, identical_answers: bool}|null $flags */
    public function __construct(
        public int $submissionId,
        public string $unitCode,
        public string $unitName,
        public int $durationSeconds,
        public ?array $flags,
        public ?string $decision,
        public ?string $reason,
        public ?string $reviewerName,
        public ?CarbonInterface $reviewedAt,
    ) {}
}
