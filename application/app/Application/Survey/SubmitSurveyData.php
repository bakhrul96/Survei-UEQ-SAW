<?php

namespace App\Application\Survey;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

readonly class SubmitSurveyData
{
    /** @var array<int, int> */
    public array $answers;

    /** @param array<int, mixed> $answers */
    public function __construct(
        public int $periodId,
        public int $respondentId,
        public string $sessionId,
        public int $unitId,
        public string $idempotencyKey,
        public string $instrumentVersion,
        public CarbonImmutable $startedAt,
        array $answers,
    ) {
        $this->answers = self::validateAnswers($answers);
    }

    /** @param array<int, mixed> $answers
     * @return array<int, int>
     */
    private static function validateAnswers(array $answers): array
    {
        $validatedAnswers = [];

        foreach ($answers as $itemOrder => $score) {
            if (! is_int($score)) {
                throw new InvalidArgumentException('Nilai jawaban harus berupa bilangan bulat.');
            }

            $validatedAnswers[$itemOrder] = $score;
        }

        return $validatedAnswers;
    }
}
