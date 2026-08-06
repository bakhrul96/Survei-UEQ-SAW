<?php

namespace App\Application\Study;

use App\Domain\Study\PeriodStatus;
use App\Domain\Study\ReadinessEvidenceKind;
use App\Models\EvaluationPeriod;
use App\Models\PeriodReadinessEvidence;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RecordReadinessEvidence
{
    public function handle(
        EvaluationPeriod $period,
        User $actor,
        ReadinessEvidenceKind $kind,
        string $reference,
        string $notes,
    ): PeriodReadinessEvidence {
        return DB::transaction(function () use ($period, $actor, $kind, $reference, $notes): PeriodReadinessEvidence {
            $lockedPeriod = EvaluationPeriod::query()->lockForUpdate()->findOrFail($period->id);

            throw_unless(auth()->id() === $actor->id, DomainException::class, 'Admin harus terautentikasi untuk memverifikasi bukti kesiapan.');
            throw_unless($lockedPeriod->status === PeriodStatus::Draft, DomainException::class, 'Bukti kesiapan hanya dapat diubah saat periode masih draft.');

            $readyAdminIds = User::query()
                ->lockForUpdate()
                ->whereNotNull('email_verified_at')
                ->whereNotNull('two_factor_secret')
                ->whereNotNull('two_factor_confirmed_at')
                ->pluck('id');

            throw_unless(
                $readyAdminIds->count() === 1 && $readyAdminIds->first() === $actor->id,
                DomainException::class,
                'Bukti kesiapan harus diverifikasi oleh satu-satunya admin terverifikasi dengan 2FA aktif.',
            );

            $reference = trim($reference);
            $notes = trim($notes);

            throw_unless($reference !== '', DomainException::class, 'Referensi bukti kesiapan wajib diisi.');
            throw_unless($notes !== '', DomainException::class, 'Catatan bukti kesiapan wajib diisi.');

            if ($kind === ReadinessEvidenceKind::Https) {
                $validUrl = filter_var($reference, FILTER_VALIDATE_URL) !== false;
                $httpsScheme = strtolower((string) parse_url($reference, PHP_URL_SCHEME)) === 'https';

                throw_unless($validUrl && $httpsScheme, DomainException::class, 'Referensi HTTPS harus berupa URL https yang valid.');
            } else {
                throw_unless(mb_strlen($notes) >= 20, DomainException::class, 'Catatan bukti harus berisi minimal 20 karakter.');
            }

            return PeriodReadinessEvidence::query()->updateOrCreate(
                [
                    'evaluation_period_id' => $lockedPeriod->id,
                    'kind' => $kind,
                ],
                [
                    'reference' => $reference,
                    'notes' => $notes,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ],
            );
        }, attempts: 3);
    }
}
