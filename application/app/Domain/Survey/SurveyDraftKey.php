<?php

namespace App\Domain\Survey;

final class SurveyDraftKey
{
    public static function for(int $periodId, int $respondentId, int $unitId, string $version): string
    {
        return implode(':', ['ueq-draft-v1', $periodId, $respondentId, $unitId, $version]);
    }
}
