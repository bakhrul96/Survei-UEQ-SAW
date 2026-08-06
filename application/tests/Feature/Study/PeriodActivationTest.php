<?php

use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\ReadinessEvidenceKind;
use App\Domain\Study\StudyConfigurationHasher;
use App\Livewire\Admin\StudySettings;
use App\Models\EvaluationPeriod;
use App\Models\PeriodReadinessEvidence;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
});

it('rejects activation while instrument and benchmarks are unverified', function () {
    $period = EvaluationPeriod::firstOrFail();

    $issues = app(PeriodReadinessService::class)->issues($period);

    expect($issues)->toContain('Instrumen UEQ belum diverifikasi.')
        ->and($issues)->toContain('Enam benchmark belum diverifikasi.');
});

it('reports every missing operational readiness requirement', function () {
    $period = EvaluationPeriod::firstOrFail();

    expect(app(PeriodReadinessService::class)->issues($period))
        ->toContain('Tepat satu admin terverifikasi dengan 2FA aktif wajib tersedia.')
        ->toContain('Bukti HTTPS belum diverifikasi.')
        ->toContain('Bukti uji pemulihan backup belum diverifikasi.')
        ->toContain('Bukti uji submit survei belum diverifikasi.');
});

it('keeps activation blocked until all three operational evidence records exist', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update([
        'instrument_source' => 'UEQ Bahasa Indonesia terverifikasi',
        'instrument_verified_at' => now(),
        'opens_at' => now(),
        'closes_at' => now()->addMonth(),
    ]);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    releaseOneReadyAdminAndEvidence($period);
    PeriodReadinessEvidence::query()->where('kind', ReadinessEvidenceKind::SubmitTest)->delete();

    expect(fn () => app(PeriodReadinessService::class)->activate($period->fresh()))
        ->toThrow(DomainException::class, 'Bukti uji submit survei belum diverifikasi.');
});

it('locks configuration when every readiness rule passes', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update([
        'instrument_source' => 'UEQ Bahasa Indonesia terverifikasi',
        'instrument_verified_at' => now(),
        'opens_at' => now(),
        'closes_at' => now()->addMonth(),
    ]);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    releaseOneReadyAdminAndEvidence($period);

    $activated = app(PeriodReadinessService::class)->activate($period->fresh());

    expect($activated->status)->toBe(PeriodStatus::Active)
        ->and($activated->configuration_locked_at)->not->toBeNull()
        ->and($activated->configuration_hash)->toBe(app(StudyConfigurationHasher::class)->hash($activated));
});

it('does not activate when readiness issues exist', function () {
    $period = EvaluationPeriod::firstOrFail();

    expect(fn () => app(PeriodReadinessService::class)->activate($period))
        ->toThrow(DomainException::class, 'Instrumen UEQ belum diverifikasi.');

    expect($period->fresh()->status)->toBe(PeriodStatus::Draft)
        ->and($period->fresh()->configuration_locked_at)->toBeNull();
});

it('requires login for settings', function () {
    $this->get(route('admin.study-settings'))->assertRedirect('/login');
});

it('does not let an active period change locked fields', function () {
    $admin = User::factory()->create();
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('minimumPerUnit', 99)
        ->call('save')
        ->assertForbidden();

    expect($period->fresh()->minimum_per_unit)->toBe(20);
});

it('lets an admin verify, activate, and close the seeded period', function () {
    $period = EvaluationPeriod::firstOrFail();
    $admin = releaseOneReadyAdminAndEvidence($period);
    $settings = Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('opensAt', now()->format('Y-m-d\TH:i'))
        ->set('closesAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('save')
        ->set('instrumentSource', 'Sumber instrumen UEQ')
        ->call('verifyInstrument');

    UeqBenchmark::query()->where('version', $period->instrument_version)->pluck('id')->each(
        fn (int $id) => $settings->call('verifyBenchmark', $id),
    );

    $settings->call('activate')->call('close')->assertHasNoErrors();

    expect($period->fresh()->status)->toBe(PeriodStatus::Closed)
        ->and($period->fresh()->instrument_verified_at)->not->toBeNull()
        ->and(UeqBenchmark::query()->where('version', $period->instrument_version)->whereNotNull('verified_at')->count())->toBe(6);
});

it('invalidates instrument verification when its source changes', function () {
    $admin = User::factory()->create();
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['instrument_source' => 'Sumber lama', 'instrument_verified_at' => now()]);

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('opensAt', now()->format('Y-m-d\TH:i'))
        ->set('closesAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->set('instrumentSource', 'Sumber baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($period->fresh()->instrument_source)->toBe('Sumber baru')
        ->and($period->fresh()->instrument_verified_at)->toBeNull();
});

it('shows an actionable error when instrument verification has no source', function () {
    $admin = User::factory()->create();

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('instrumentSource', '   ')
        ->call('verifyInstrument')
        ->assertHasErrors(['instrumentVerification']);
});

it('rejects malformed current-version item and wrong-version benchmark readiness', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['instrument_source' => 'Sumber', 'instrument_verified_at' => now()]);
    UeqBenchmark::query()->where('version', $period->instrument_version)->update(['verified_at' => null]);
    foreach (['Attractiveness', 'Perspicuity', 'Efficiency', 'Dependability', 'Stimulation', 'Novelty'] as $scale) {
        UeqBenchmark::query()->create([
            'version' => 'UEQ-LAIN',
            'scale' => $scale,
            'good_threshold' => 1.50,
            'source' => 'Sumber lain',
            'verified_at' => now(),
        ]);
    }
    UeqItem::query()->where('version', $period->instrument_version)->where('order', 1)->update(['order' => 27, 'scale' => 'Invalid', 'positive_pole' => 'middle']);

    expect(app(PeriodReadinessService::class)->issues($period->fresh()))
        ->toContain('Nomor item instrumen harus tepat 1 sampai 26.')
        ->toContain('Skala item instrumen tidak valid.')
        ->toContain('Kutub positif item instrumen tidak valid.')
        ->toContain('Enam benchmark belum diverifikasi.');
});

it('rejects incomplete consent and quality configuration before activation', function (string $attribute, mixed $value, string $message) {
    $period = EvaluationPeriod::firstOrFail();
    $period->forceFill([
        'consent_data_description' => 'Jawaban UEQ mentah dan metadata pengisian.',
        'consent_cookie_description' => 'Cookie anonim mencegah penilaian modul yang sama.',
        'consent_estimated_minutes' => 10,
        'consent_withdrawal_description' => 'Partisipasi dapat dihentikan sebelum submit.',
        'research_contact' => 'peneliti@example.test',
        'quality_rules_version' => 'quality-rules-v1',
        'identical_answers_flag_enabled' => true,
        $attribute => $value,
    ]);

    expect(app(PeriodReadinessService::class)->issues($period))->toContain($message);
})->with([
    'stored data' => ['consent_data_description', '', 'Deskripsi data consent wajib diisi.'],
    'cookie use' => ['consent_cookie_description', '', 'Penjelasan cookie consent wajib diisi.'],
    'estimated time' => ['consent_estimated_minutes', 0, 'Estimasi waktu consent harus minimal satu menit.'],
    'withdrawal right' => ['consent_withdrawal_description', '', 'Hak berhenti consent wajib dijelaskan.'],
    'research contact' => ['research_contact', '', 'Kontak penelitian wajib diisi.'],
    'quality rules version' => ['quality_rules_version', '', 'Versi aturan kualitas wajib diisi.'],
    'identical-answer rule' => ['identical_answers_flag_enabled', false, 'Aturan jawaban identik wajib diaktifkan.'],
    'fast-response threshold' => ['fast_response_seconds', 0, 'Ambang respons cepat harus lebih besar dari nol.'],
]);
