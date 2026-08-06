<?php

namespace App\Domain\Study;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\PeriodReadinessEvidence;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PeriodReadinessService
{
    private const UEQ_SCALES = ['Attractiveness', 'Perspicuity', 'Efficiency', 'Dependability', 'Stimulation', 'Novelty'];

    public function __construct(private readonly StudyConfigurationHasher $hasher) {}

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
        if (trim((string) $period->consent_data_description) === '') {
            $issues[] = 'Deskripsi data consent wajib diisi.';
        }
        if (trim((string) $period->consent_cookie_description) === '') {
            $issues[] = 'Penjelasan cookie consent wajib diisi.';
        }
        if ((int) $period->consent_estimated_minutes < 1) {
            $issues[] = 'Estimasi waktu consent harus minimal satu menit.';
        }
        if (trim((string) $period->consent_withdrawal_description) === '') {
            $issues[] = 'Hak berhenti consent wajib dijelaskan.';
        }
        if (trim((string) $period->research_contact) === '') {
            $issues[] = 'Kontak penelitian wajib diisi.';
        }
        if ((int) $period->fast_response_seconds < 1) {
            $issues[] = 'Ambang respons cepat harus lebih besar dari nol.';
        }
        if (trim((string) $period->quality_rules_version) === '') {
            $issues[] = 'Versi aturan kualitas wajib diisi.';
        }
        if (! $period->identical_answers_flag_enabled) {
            $issues[] = 'Aturan jawaban identik wajib diaktifkan.';
        }
        if (! $this->weightsTotalOne([
            $period->sensitivity_s1_c1,
            $period->sensitivity_s1_c2,
            $period->sensitivity_s1_c3,
        ])) {
            $issues[] = 'Bobot S1 harus berjumlah tepat 1,000000.';
        }
        if (! $this->weightsTotalOne([
            $period->sensitivity_s2_c1,
            $period->sensitivity_s2_c2,
            $period->sensitivity_s2_c3,
        ])) {
            $issues[] = 'Bobot S2 harus berjumlah tepat 1,000000.';
        }
        if (User::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('two_factor_secret')
            ->whereNotNull('two_factor_confirmed_at')
            ->count() !== 1) {
            $issues[] = 'Tepat satu admin terverifikasi dengan 2FA aktif wajib tersedia.';
        }

        $evidenceKinds = PeriodReadinessEvidence::query()
            ->where('evaluation_period_id', $period->id)
            ->toBase()
            ->pluck('kind');

        if (! $evidenceKinds->contains(ReadinessEvidenceKind::Https->value)) {
            $issues[] = 'Bukti HTTPS belum diverifikasi.';
        }
        if (! $evidenceKinds->contains(ReadinessEvidenceKind::BackupRestore->value)) {
            $issues[] = 'Bukti uji pemulihan backup belum diverifikasi.';
        }
        if (! $evidenceKinds->contains(ReadinessEvidenceKind::SubmitTest->value)) {
            $issues[] = 'Bukti uji submit survei belum diverifikasi.';
        }
        if (! $period->instrument_verified_at || ! $period->instrument_source) {
            $issues[] = 'Instrumen UEQ belum diverifikasi.';
        }
        if (EvaluationUnit::query()->where('is_active', true)->count() !== 13) {
            $issues[] = 'Harus tersedia tepat 13 modul aktif.';
        }
        $items = UeqItem::query()->where('version', $period->instrument_version)->get(['order', 'scale', 'positive_pole']);

        if ($items->count() !== 26) {
            $issues[] = 'Versi instrumen harus memiliki tepat 26 item.';
        }
        if ($items->count() === 26 && $items->pluck('order')->sort()->values()->all() !== range(1, 26)) {
            $issues[] = 'Nomor item instrumen harus tepat 1 sampai 26.';
        }
        if ($items->contains(fn (UeqItem $item): bool => ! in_array($item->scale, self::UEQ_SCALES, true))) {
            $issues[] = 'Skala item instrumen tidak valid.';
        }
        if ($items->contains(fn (UeqItem $item): bool => ! in_array($item->positive_pole, ['left', 'right'], true))) {
            $issues[] = 'Kutub positif item instrumen tidak valid.';
        }

        $verifiedBenchmarks = UeqBenchmark::query()
            ->where('version', $period->instrument_version)
            ->whereNotNull('verified_at')
            ->get(['scale']);

        if ($verifiedBenchmarks->count() !== 6
            || $verifiedBenchmarks->pluck('scale')->unique()->sort()->values()->all() !== collect(self::UEQ_SCALES)->sort()->values()->all()) {
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
                'configuration_hash' => $this->hasher->hash($lockedPeriod),
            ]);

            $lockedPeriod->refresh();

            return $lockedPeriod;
        });
    }

    /** @param list<mixed> $weights */
    private function weightsTotalOne(array $weights): bool
    {
        if (count(array_filter($weights, is_numeric(...))) !== 3) {
            return false;
        }

        $numericWeights = array_map('floatval', $weights);

        return ! collect($numericWeights)->contains(fn (float $weight): bool => $weight < 0.0 || $weight > 1.0)
            && abs(array_sum($numericWeights) - 1.0) <= 0.000001;
    }
}
