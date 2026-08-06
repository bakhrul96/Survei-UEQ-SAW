<?php

namespace App\Domain\Study;

use App\Models\EvaluationPeriod;
use DomainException;

final class SurveyPeriodGate
{
    public function __construct(private readonly StudyConfigurationHasher $hasher) {}

    /** @return list<string> */
    public function issues(EvaluationPeriod $period): array
    {
        $issues = [];

        if ($period->status !== PeriodStatus::Active) {
            $issues[] = 'Periode penelitian tidak aktif.';
        }

        if ($period->opens_at === null || now()->lt($period->opens_at)) {
            $issues[] = 'Periode penelitian belum dibuka.';
        }

        if ($period->closes_at === null || now()->gt($period->closes_at)) {
            $issues[] = 'Periode penelitian sudah ditutup.';
        }

        if ($period->configuration_locked_at === null) {
            $issues[] = 'Konfigurasi periode belum dikunci.';
        }

        if ($period->configuration_hash === null
            || ! hash_equals($period->configuration_hash, $this->hasher->hash($period))) {
            $issues[] = 'Konfigurasi periode berubah setelah dikunci.';
        }

        return array_values(array_unique($issues));
    }

    public function assertAccepting(EvaluationPeriod $period): void
    {
        $issues = $this->issues($period);

        throw_if($issues !== [], DomainException::class, implode(' ', $issues));
    }
}
