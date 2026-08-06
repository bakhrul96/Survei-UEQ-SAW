<?php

namespace App\Application\Survey;

use App\Domain\Study\SurveyPeriodGate;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class SubmitSurvey
{
    public function __construct(
        private readonly SurveyPeriodGate $periodGate,
    ) {}

    public function handle(SubmitSurveyData $data): SurveySubmission
    {
        $period = EvaluationPeriod::query()->findOrFail($data->periodId);
        $this->periodGate->assertAccepting($period);

        $profileExists = RespondentProfile::query()
            ->where('evaluation_period_id', $data->periodId)
            ->where('anonymous_respondent_id', $data->respondentId)
            ->where('eligible', true)
            ->exists();
        throw_unless($profileExists, DomainException::class, 'Responden tidak memenuhi syarat.');

        $unitExists = EvaluationUnit::query()
            ->whereKey($data->unitId)
            ->where('is_active', true)
            ->exists();
        throw_unless($unitExists, DomainException::class, 'Modul tidak tersedia.');
        throw_unless(
            $data->instrumentVersion === $period->instrument_version,
            DomainException::class,
            'Versi instrumen tidak sesuai.',
        );

        $attempts = RateLimiter::increment(
            'survey-submit:'.$data->respondentId,
            60,
        );

        throw_if(
            $attempts > (int) config('survey.submit_attempts_per_minute'),
            DomainException::class,
            'Terlalu banyak percobaan submit. Coba kembali dalam satu menit.',
        );

        try {
            return DB::transaction(function () use ($data): SurveySubmission {
                $existing = SurveySubmission::query()
                    ->where('idempotency_key', $data->idempotencyKey)
                    ->first();

                if ($existing instanceof SurveySubmission) {
                    return $existing;
                }

                throw_unless(count($data->answers) === 26, DomainException::class, 'Jawaban harus tepat 26 item.');
                throw_unless(array_keys($data->answers) === range(1, 26), DomainException::class, 'Nomor item harus lengkap 1 sampai 26.');
                throw_unless(collect($data->answers)->every(fn (int $score): bool => $score >= 1 && $score <= 7), DomainException::class, 'Nilai jawaban harus 1 sampai 7.');

                $duplicate = SurveySubmission::query()
                    ->where('evaluation_period_id', $data->periodId)
                    ->where('anonymous_respondent_id', $data->respondentId)
                    ->where('evaluation_unit_id', $data->unitId)
                    ->lockForUpdate()
                    ->exists();
                throw_if($duplicate, DomainException::class, 'Modul ini sudah pernah dinilai.');

                $completedAt = now();
                $durationSeconds = max(1, (int) ceil($data->startedAt->diffInSeconds($completedAt)));
                $session = SurveySession::query()->lockForUpdate()->findOrFail($data->sessionId);
                throw_unless(
                    $session->evaluation_period_id === $data->periodId && $session->anonymous_respondent_id === $data->respondentId,
                    DomainException::class,
                    'Sesi survei tidak sesuai.',
                );

                $submission = SurveySubmission::query()->create([
                    'evaluation_period_id' => $data->periodId,
                    'anonymous_respondent_id' => $data->respondentId,
                    'survey_session_id' => $data->sessionId,
                    'evaluation_unit_id' => $data->unitId,
                    'idempotency_key' => $data->idempotencyKey,
                    'instrument_version' => $data->instrumentVersion,
                    'started_at' => $data->startedAt,
                    'completed_at' => $completedAt,
                    'duration_seconds' => $durationSeconds,
                    'session_sequence' => $session->submitted_count + 1,
                    'status' => 'submitted',
                ]);

                $submission->answers()->createMany(collect($data->answers)->map(
                    fn (int $score, int $itemOrder): array => ['item_order' => $itemOrder, 'raw_score' => $score],
                )->values()->all());

                $session->update([
                    'submitted_count' => $session->submitted_count + 1,
                    'last_activity_at' => $completedAt,
                ]);

                return $submission;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (! $this->isIdempotencyKeyUniqueViolation($exception)) {
                throw $exception;
            }

            return SurveySubmission::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->firstOrFail();
        }
    }

    private function isIdempotencyKeyUniqueViolation(QueryException $exception): bool
    {
        $driverCode = $exception->errorInfo[1] ?? null;
        $isKnownUniqueViolation = in_array($driverCode, [19, 1062], true)
            || $exception->getCode() === '23000';

        return $isKnownUniqueViolation
            && str_contains(strtolower($exception->getMessage()), 'idempotency_key');
    }
}
