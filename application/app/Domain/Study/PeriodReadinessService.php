<?php

namespace App\Domain\Study;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use DomainException;
use Illuminate\Support\Facades\DB;

class PeriodReadinessService
{
    /**
     * @return array<string>
     */
    public function issues(EvaluationPeriod $period): array
    {
        $issues = [];

        if ($period->status !== PeriodStatus::Draft) {
            $issues[] = 'Periode bukan draft.';
        }
        if (! $period->opens_at || ! $period->closes_at || $period->closes_at <= $period->opens_at) {
            $issues[] = 'Tanggal periode tidak valid.';
        }
        if ($period->minimum_age < 17) {
            $issues[] = 'Usia minimum harus sedikitnya 17 tahun.';
        }
        if ($period->minimum_per_unit < 1 || $period->target_per_unit < $period->minimum_per_unit) {
            $issues[] = 'Target per modul tidak valid.';
        }
        if (trim($period->target_basis) === '') {
            $issues[] = 'Dasar target sampel wajib dicatat.';
        }
        if (trim($period->consent_text) === '') {
            $issues[] = 'Teks consent wajib diisi.';
        }
        if (! $period->instrument_verified_at || ! $period->instrument_source) {
            $issues[] = 'Instrumen UEQ belum diverifikasi.';
        }
        if (EvaluationUnit::query()->where('is_active', true)->count() !== 13) {
            $issues[] = 'Harus tersedia tepat 13 modul aktif.';
        }
        if (UeqItem::query()->where('version', $period->instrument_version)->count() !== 26) {
            $issues[] = 'Versi instrumen harus memiliki tepat 26 item.';
        }
        if (UeqBenchmark::query()->whereNotNull('verified_at')->count() !== 6) {
            $issues[] = 'Enam benchmark belum diverifikasi.';
        }
        if (EvaluationPeriod::query()
            ->where('status', PeriodStatus::Active->value)
            ->where('id', '!=', $period->id)
            ->exists()) {
            $issues[] = 'Periode aktif lain sudah tersedia.';
        }

        return $issues;
    }

    public function activate(EvaluationPeriod $period): EvaluationPeriod
    {
        return DB::transaction(function () use ($period): EvaluationPeriod {
            EvaluationPeriod::query()->lockForUpdate()->get();

            $lockedPeriod = EvaluationPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            $issues = $this->issues($lockedPeriod);

            if ($issues !== []) {
                throw new DomainException(implode(' ', $issues));
            }

            $lockedPeriod->update([
                'status' => PeriodStatus::Active,
                'configuration_locked_at' => now(),
            ]);

            $lockedPeriod->refresh();

            return $lockedPeriod;
        });
    }
}
