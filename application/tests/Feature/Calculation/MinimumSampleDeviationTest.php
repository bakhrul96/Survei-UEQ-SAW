<?php

use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Models\AuditEvent;
use Tests\Support\GoldenFixture;

it('records an approved minimum sample deviation with an audit event', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $actor = $run->creator;

    $recorded = app(RecordMinimumSampleDeviation::class)->handle(
        $run,
        'Responden yang memenuhi kriteria tidak lagi tersedia.',
        'Notulen bimbingan 2026-08-06',
        $actor,
    );

    expect($recorded->minimum_deviation_reason)->toBe('Responden yang memenuhi kriteria tidak lagi tersedia.')
        ->and($recorded->minimum_deviation_approval_reference)->toBe('Notulen bimbingan 2026-08-06')
        ->and($recorded->minimum_deviation_approved_by)->toBe($actor->id)
        ->and($recorded->minimum_deviation_approved_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'calculation_run.minimum_deviation_recorded')->count())->toBe(1);
});

it('rejects blank deviation evidence', function (string $reason, string $reference): void {
    $run = GoldenFixture::persistedClosedRun();

    expect(fn () => app(RecordMinimumSampleDeviation::class)->handle($run, $reason, $reference, $run->creator))
        ->toThrow(DomainException::class, 'Alasan dan referensi persetujuan pembimbing wajib diisi.');
})->with([
    'blank reason' => ['', 'Notulen bimbingan'],
    'blank reference' => ['Alasan operasional', '   '],
]);

it('only records minimum sample deviation on a preview run', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $run->update(['status' => 'stale']);

    expect(fn () => app(RecordMinimumSampleDeviation::class)->handle($run, 'Alasan', 'Referensi', $run->creator))
        ->toThrow(DomainException::class, 'Keputusan penyimpangan hanya dapat dicatat pada run preview.');
});
