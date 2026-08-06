<?php

use App\Application\Study\RecordReadinessEvidence;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\ReadinessEvidenceKind;
use App\Livewire\Admin\StudySettings;
use App\Models\EvaluationPeriod;
use App\Models\PeriodReadinessEvidence;
use App\Models\User;
use Livewire\Livewire;

it('records each readiness evidence kind for the sole authenticated ready admin', function (ReadinessEvidenceKind $kind, string $reference, string $notes) {
    $period = EvaluationPeriod::factory()->create();
    $admin = User::factory()->withTwoFactor()->create();

    $evidence = $this->actingAs($admin)->app->make(RecordReadinessEvidence::class)->handle(
        $period,
        $admin,
        $kind,
        "  {$reference}  ",
        "  {$notes}  ",
    );

    expect($evidence->kind)->toBe($kind)
        ->and($evidence->reference)->toBe($reference)
        ->and($evidence->notes)->toBe($notes)
        ->and($evidence->verified_by)->toBe($admin->id)
        ->and($evidence->verified_at)->not->toBeNull();
})->with([
    'https' => [ReadinessEvidenceKind::Https, 'https://survei.wongreang.example', 'TLS dan pengalihan HTTPS berhasil diverifikasi.'],
    'backup restore' => [ReadinessEvidenceKind::BackupRestore, 'ueq_saw_20260806_1200.sql', 'Backup dipulihkan dan data hasil restore telah diperiksa.'],
    'submit test' => [ReadinessEvidenceKind::SubmitTest, 'SurveyHappyPathTest 1 test / 8 assertions', 'Alur submit lengkap berhasil dijalankan tanpa kegagalan.'],
]);

it('updates corrected evidence instead of creating a duplicate', function () {
    $period = EvaluationPeriod::factory()->create();
    $admin = User::factory()->withTwoFactor()->create();
    $action = $this->actingAs($admin)->app->make(RecordReadinessEvidence::class);

    $action->handle($period, $admin, ReadinessEvidenceKind::Https, 'https://lama.example', 'HTTPS lama telah diperiksa.');
    $action->handle($period, $admin, ReadinessEvidenceKind::Https, 'https://baru.example', 'HTTPS baru telah diperiksa.');

    expect(PeriodReadinessEvidence::query()->count())->toBe(1)
        ->and(PeriodReadinessEvidence::query()->sole()->reference)->toBe('https://baru.example');
});

it('rejects evidence unless the actor is the sole authenticated verified 2FA admin', function (Closure $arrange) {
    $period = EvaluationPeriod::factory()->create();
    $admin = User::factory()->withTwoFactor()->create();
    $arrange($this, $admin);

    expect(fn () => app(RecordReadinessEvidence::class)->handle(
        $period,
        $admin,
        ReadinessEvidenceKind::Https,
        'https://survei.wongreang.example',
        'HTTPS berhasil diperiksa sebelum aktivasi.',
    ))->toThrow(DomainException::class);
})->with([
    'unauthenticated' => [fn () => null],
    'different authenticated actor' => [fn ($test, User $admin) => $test->actingAs(User::factory()->create())],
    'unverified email' => [function ($test, User $admin): void {
        $admin->update(['email_verified_at' => null]);
        $test->actingAs($admin);
    }],
    'unconfirmed 2FA' => [function ($test, User $admin): void {
        $admin->forceFill(['two_factor_confirmed_at' => null])->save();
        $test->actingAs($admin);
    }],
    'more than one ready admin' => [function ($test, User $admin): void {
        User::factory()->withTwoFactor()->create();
        $test->actingAs($admin);
    }],
]);

it('rejects evidence mutation after activation', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $admin = User::factory()->withTwoFactor()->create();

    expect(fn () => $this->actingAs($admin)->app->make(RecordReadinessEvidence::class)->handle(
        $period,
        $admin,
        ReadinessEvidenceKind::Https,
        'https://survei.wongreang.example',
        'HTTPS berhasil diperiksa sebelum aktivasi.',
    ))->toThrow(DomainException::class, 'Bukti kesiapan hanya dapat diubah saat periode masih draft.');
});

it('validates evidence details', function (ReadinessEvidenceKind $kind, string $reference, string $notes) {
    $period = EvaluationPeriod::factory()->create();
    $admin = User::factory()->withTwoFactor()->create();

    expect(fn () => $this->actingAs($admin)->app->make(RecordReadinessEvidence::class)->handle(
        $period,
        $admin,
        $kind,
        $reference,
        $notes,
    ))->toThrow(DomainException::class);
})->with([
    'empty reference' => [ReadinessEvidenceKind::Https, '   ', 'HTTPS berhasil diperiksa sebelum aktivasi.'],
    'empty notes' => [ReadinessEvidenceKind::Https, 'https://survei.wongreang.example', '   '],
    'non-HTTPS URL' => [ReadinessEvidenceKind::Https, 'http://survei.wongreang.example', 'HTTPS berhasil diperiksa sebelum aktivasi.'],
    'invalid URL' => [ReadinessEvidenceKind::Https, 'bukan-url', 'HTTPS berhasil diperiksa sebelum aktivasi.'],
    'short backup notes' => [ReadinessEvidenceKind::BackupRestore, 'ueq_saw.sql', 'Sudah diuji.'],
    'short submit notes' => [ReadinessEvidenceKind::SubmitTest, 'SurveyHappyPathTest', 'Sudah diuji.'],
]);

it('lets the ready admin record and review evidence from study settings', function () {
    $period = EvaluationPeriod::factory()->create();
    $admin = User::factory()->withTwoFactor()->create();

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->assertSee('https://survei.wongreang.example')
        ->assertSee('ueq_saw_20260806_1200.sql')
        ->assertSee('SurveyHappyPathTest 1 test / 8 assertions')
        ->set('evidenceReferences.https', 'https://rilis.example')
        ->set('evidenceNotes.https', 'HTTPS rilis telah diverifikasi oleh admin.')
        ->call('recordEvidence', ReadinessEvidenceKind::Https->value)
        ->assertHasNoErrors()
        ->assertSee('https://rilis.example');

    expect($period->readinessEvidence()->where('kind', ReadinessEvidenceKind::Https)->exists())->toBeTrue();
});
