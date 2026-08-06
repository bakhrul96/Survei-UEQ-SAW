<?php

namespace App\Livewire\Admin;

use App\Application\Study\RecordReadinessEvidence;
use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\ReadinessEvidenceKind;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use App\Models\User;
use DomainException;
use Illuminate\View\View;
use Livewire\Component;

class StudySettings extends Component
{
    public int $periodId;

    public string $opensAt = '';

    public string $closesAt = '';

    public int $minimumAge;

    public int $minimumPerUnit;

    public int $targetPerUnit;

    public string $targetBasis = '';

    public string $consentText = '';

    public string $consentDataDescription = '';

    public string $consentCookieDescription = '';

    public int $consentEstimatedMinutes = 10;

    public string $consentWithdrawalDescription = '';

    public string $researchContact = '';

    public int $fastResponseSeconds = 120;

    public string $qualityRulesVersion = '';

    public bool $identicalAnswersFlagEnabled = true;

    public string $instrumentSource = '';

    /** @var array<string, string> */
    public array $evidenceReferences = [
        'https' => '',
        'backup_restore' => '',
        'submit_test' => '',
    ];

    /** @var array<string, string> */
    public array $evidenceNotes = [
        'https' => '',
        'backup_restore' => '',
        'submit_test' => '',
    ];

    public function mount(): void
    {
        $period = EvaluationPeriod::query()->firstOrFail();

        $this->periodId = $period->id;
        $this->fillFromPeriod($period);
    }

    public function save(): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        $validated = $this->validate([
            'opensAt' => ['required', 'date'],
            'closesAt' => ['required', 'date', 'after:opensAt'],
            'minimumAge' => ['required', 'integer', 'min:17'],
            'minimumPerUnit' => ['required', 'integer', 'min:1'],
            'targetPerUnit' => ['required', 'integer', 'gte:minimumPerUnit'],
            'targetBasis' => ['required', 'string'],
            'consentText' => ['required', 'string'],
            'consentDataDescription' => ['required', 'string'],
            'consentCookieDescription' => ['required', 'string'],
            'consentEstimatedMinutes' => ['required', 'integer', 'min:1'],
            'consentWithdrawalDescription' => ['required', 'string'],
            'researchContact' => ['required', 'string'],
            'fastResponseSeconds' => ['required', 'integer', 'min:1'],
            'qualityRulesVersion' => ['required', 'string'],
            'identicalAnswersFlagEnabled' => ['accepted'],
            'instrumentSource' => ['nullable', 'string'],
        ]);

        $instrumentSource = trim((string) $validated['instrumentSource']) ?: null;

        $period->update([
            'opens_at' => $validated['opensAt'],
            'closes_at' => $validated['closesAt'],
            'minimum_age' => $validated['minimumAge'],
            'minimum_per_unit' => $validated['minimumPerUnit'],
            'target_per_unit' => $validated['targetPerUnit'],
            'target_basis' => trim($validated['targetBasis']),
            'consent_text' => trim($validated['consentText']),
            'consent_data_description' => trim($validated['consentDataDescription']),
            'consent_cookie_description' => trim($validated['consentCookieDescription']),
            'consent_estimated_minutes' => $validated['consentEstimatedMinutes'],
            'consent_withdrawal_description' => trim($validated['consentWithdrawalDescription']),
            'research_contact' => trim($validated['researchContact']),
            'fast_response_seconds' => $validated['fastResponseSeconds'],
            'quality_rules_version' => trim($validated['qualityRulesVersion']),
            'identical_answers_flag_enabled' => true,
            'instrument_source' => $instrumentSource,
            'instrument_verified_at' => $instrumentSource === $period->instrument_source
                ? $period->instrument_verified_at
                : null,
        ]);

        $this->fillFromPeriod($period->fresh());
    }

    public function activate(PeriodReadinessService $readiness): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        try {
            $activated = $readiness->activate($period);
            $this->fillFromPeriod($activated);
            session()->flash('status', 'Periode berhasil diaktifkan dan konfigurasi dikunci.');
        } catch (DomainException $exception) {
            $this->addError('activation', $exception->getMessage());
        }
    }

    public function verifyInstrument(): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        try {
            $source = trim($this->instrumentSource);
            throw_unless($source !== '', DomainException::class, 'Sumber instrumen wajib diisi sebelum verifikasi.');

            $period->update(['instrument_source' => $source, 'instrument_verified_at' => now()]);
            $this->fillFromPeriod($period->fresh());
        } catch (DomainException $exception) {
            $this->addError('instrumentVerification', $exception->getMessage());
        }
    }

    public function verifyBenchmark(int $benchmarkId): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        UeqBenchmark::query()
            ->whereKey($benchmarkId)
            ->where('version', $period->instrument_version)
            ->update(['verified_at' => now()]);
    }

    public function recordEvidence(string $kind, RecordReadinessEvidence $recorder): void
    {
        $evidenceKind = ReadinessEvidenceKind::tryFrom($kind);
        abort_if($evidenceKind === null, 404);

        $validated = $this->validate([
            "evidenceReferences.{$kind}" => ['required', 'string'],
            "evidenceNotes.{$kind}" => ['required', 'string'],
        ]);

        $actor = auth()->user();
        abort_unless($actor instanceof User, 401);

        try {
            $recorder->handle(
                $this->period(),
                $actor,
                $evidenceKind,
                $validated['evidenceReferences'][$kind],
                $validated['evidenceNotes'][$kind],
            );
            session()->flash('evidenceStatus', 'Bukti kesiapan berhasil disimpan.');
        } catch (DomainException $exception) {
            $this->addError("evidence.{$kind}", $exception->getMessage());
        }
    }

    public function close(): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Active, 403);

        $period->update(['status' => PeriodStatus::Closed]);
        $this->fillFromPeriod($period->fresh());
        session()->flash('status', 'Periode berhasil ditutup.');
    }

    public function render(PeriodReadinessService $readiness): View
    {
        $period = $this->period()->load('readinessEvidence.verifier');

        return view('livewire.admin.study-settings', [
            'period' => $period,
            'issues' => $readiness->issues($period),
            'isDraft' => $period->status === PeriodStatus::Draft,
            'benchmarks' => UeqBenchmark::query()->where('version', $period->instrument_version)->orderBy('scale')->get(),
            'evidenceDefinitions' => [
                ReadinessEvidenceKind::Https->value => [
                    'label' => 'HTTPS production',
                    'example' => 'https://survei.wongreang.example',
                ],
                ReadinessEvidenceKind::BackupRestore->value => [
                    'label' => 'Uji pemulihan backup',
                    'example' => 'ueq_saw_20260806_1200.sql',
                ],
                ReadinessEvidenceKind::SubmitTest->value => [
                    'label' => 'Uji submit survei',
                    'example' => 'SurveyHappyPathTest 1 test / 8 assertions',
                ],
            ],
            'evidenceByKind' => $period->readinessEvidence->keyBy(
                fn ($evidence): string => $evidence->kind->value,
            ),
        ])->layout('layouts.app', ['title' => 'Pengaturan Studi']);
    }

    private function period(): EvaluationPeriod
    {
        return EvaluationPeriod::query()->findOrFail($this->periodId);
    }

    private function fillFromPeriod(EvaluationPeriod $period): void
    {
        $this->opensAt = $period->opens_at?->format('Y-m-d\\TH:i') ?? '';
        $this->closesAt = $period->closes_at?->format('Y-m-d\\TH:i') ?? '';
        $this->minimumAge = $period->minimum_age;
        $this->minimumPerUnit = $period->minimum_per_unit;
        $this->targetPerUnit = $period->target_per_unit;
        $this->targetBasis = $period->target_basis;
        $this->consentText = $period->consent_text;
        $this->consentDataDescription = $period->consent_data_description;
        $this->consentCookieDescription = $period->consent_cookie_description;
        $this->consentEstimatedMinutes = $period->consent_estimated_minutes;
        $this->consentWithdrawalDescription = $period->consent_withdrawal_description;
        $this->researchContact = $period->research_contact;
        $this->fastResponseSeconds = $period->fast_response_seconds;
        $this->qualityRulesVersion = $period->quality_rules_version;
        $this->identicalAnswersFlagEnabled = $period->identical_answers_flag_enabled;
        $this->instrumentSource = $period->instrument_source ?? '';
    }
}
