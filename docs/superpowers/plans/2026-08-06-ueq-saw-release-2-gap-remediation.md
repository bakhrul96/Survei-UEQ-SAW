# UEQ-SAW Release 2 Gap Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup seluruh gap audit Rilis 2 sehingga quality flag, reliabilitas UEQ, penilaian informan, lifecycle calculation run, fixture emas, dan bukti UAT memenuhi `docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md` tanpa mengubah hasil matematis fixture yang sudah benar.

**Architecture:** Remediasi mempertahankan modular monolith yang ada. Validasi dan perubahan input ditempatkan pada service domain/application, calculation snapshot menjadi sumber keterlacakan konsensus, dan Livewire hanya menyajikan data serta meneruskan input tervalidasi. Perubahan dibuat kompatibel dengan Rilis 3 yang sudah ada; sensitivitas, expert judgment, official lock, dan ekspor agregat hanya menerima regression coverage, bukan desain ulang.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4, Flux UI 2, MySQL 8, SQLite in-memory, Pest 5, Pest Browser/Playwright, PhpSpreadsheet, Tailwind CSS 4, dan Vite 8.

## Design Authority

- Sumber kebenaran: `docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md`, terutama bagian 5.2, 9.4-9.6, 10, 13.1-13.6, 17, dan 18.2.
- Baseline implementasi: `docs/superpowers/plans/2026-08-05-ueq-saw-release-2.md` dan kode pada commit `04ef366`.
- Bukti audit: transformasi, dua belas baris statistik UEQ, gap, bobot, dua baris SAW, Vi, seri, dan rank telah cocok dengan fixture bertoleransi `0.000001`; rumus tersebut tidak boleh diubah kecuali test fixture tetap identik.

## Global Constraints

- Hanya satu studi Wong Reang Apps, 13 modul tetap, satu periode aktif, dan instrumen `UEQ-ID-26-v1` dengan tepat 26 item.
- Skor mentah tetap 1 sampai 7 dan tidak pernah ditimpa oleh skor transformasi.
- Polaritas selalu berasal dari `ueq_items.positive_pole`; teks label tidak menentukan rumus saat runtime.
- Flag kualitas tidak mengeksklusi otomatis. Keputusan aktif hanya `included` atau `excluded`; eksklusi membutuhkan alasan, reviewer, dan waktu.
- Setiap informan memakai kode anonim, jumlah informan lengkap harus 3 sampai 5, seluruh 13 modul harus dinilai, urgensi harus 1 sampai 5, estimasi hari harus lebih dari nol, dan C1+C2+C3 harus tepat 100.
- Mean teknis, sample standard deviation teknis, bobot konsensus, dan completeness disimpan di calculation snapshot; nilai individual tetap berada di tabel assessment.
- Perubahan keputusan kualitas atau data informan menaikkan `calculation_input_revision`, membuat seluruh preview lama `stale`, tidak menghapus run, dan menulis audit event dalam transaksi yang sama.
- Statistik yang dapat dihitung tidak boleh dikosongkan hanya karena alpha tidak dapat dihitung. Ketidaktersediaan deskriptif dan ketidaktersediaan reliabilitas disimpan terpisah.
- Alpha per modul/skala dengan `n < 20` diberi warning; alpha di bawah `0.70` diberi warning; pooled alpha disimpan dan dilabeli `pooled` sebagai diagnostik tambahan.
- `Gap(unit, scale) = max(0, GoodThreshold(scale) - Mean(unit, scale))`; `Gap(unit)` adalah mean enam gap skala.
- SAW memakai C1 gap sebagai benefit, C2 mean hari sebagai cost, dan C3 mean urgensi sebagai benefit. Minimal dua alternatif lengkap diperlukan.
- Nilai numerik disimpan dengan presisi tinggi, ranking memakai nilai penuh, dan toleransi seri tetap `0.000001`.
- Calculation run dan seluruh UEQ/pooled/SAW result immutable setelah ditulis. Koreksi membuat run baru.
- Tidak menambahkan multi-aplikasi, organisasi, role baru, konfigurator instrumen umum, atau fitur Rilis 3 baru.
- Setiap task mengikuti red-green-refactor, menjalankan test terfokus, dan diakhiri commit kecil.

---

## File Structure

| Path | Responsibility |
|---|---|
| `application/database/migrations/2026_08_06_000017_allow_pending_quality_reviews.php` | Memisahkan flag otomatis dari keputusan review yang belum dibuat |
| `application/database/migrations/2026_08_06_000018_create_ueq_pooled_results_and_reliability_metadata.php` | Menyimpan pooled alpha dan warning reliabilitas |
| `application/app/Application/Quality/InitializeQualityReview.php` | Menghitung dan menyimpan flag segera setelah submission lengkap |
| `application/app/Application/Calculation/CalculationInputChangeRecorder.php` | Revision, stale lifecycle, dan audit input secara atomik |
| `application/app/Domain/Technical/TechnicalConsensusData.php` | DTO konsensus seluruh periode |
| `application/app/Domain/Technical/TechnicalUnitConsensus.php` | DTO mean, sample SD, n, dan completeness per unit |
| `application/app/Domain/Technical/TechnicalConsensus.php` | Menghitung konsensus deterministik dari nilai individual |
| `application/app/Models/UeqPooledResult.php` | Hasil pooled alpha immutable per run/skala |
| `application/app/Application/Calculation/UeqResultWriter.php` | Menulis deskriptif, gap, reliabilitas unit, dan pooled |
| `application/tests/Support/ReleaseTwoFixture.php` | Skenario test lengkap non-golden untuk Quality, Technical, Calculation, UI, dan Browser |
| `application/tests/Support/GoldenFixture.php` | Loader dan builder fixture emas untuk pure service serta persistence tests |
| `application/tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php` | Memastikan XLSX independen dan JSON machine-readable tetap identik |
| `application/tests/Unit/Saw/SawGoldenFixtureTest.php` | Membandingkan seluruh X/R/kontribusi/Vi/rank dengan fixture |
| `application/tests/Feature/Calculation/GoldenCalculationRunTest.php` | Membuktikan persistence end-to-end dari raw answer sampai result rows |
| `application/tests/Browser/AdminAnalysisFlowTest.php` | UAT browser Rilis 2 dengan selector unik |

## Task 1: Persist quality flags before manual review

**Files:**
- Create: `application/database/migrations/2026_08_06_000017_allow_pending_quality_reviews.php`
- Create: `application/app/Application/Quality/InitializeQualityReview.php`
- Modify: `application/app/Application/Survey/SubmitSurvey.php`
- Modify: `application/app/Models/QualityReview.php`
- Modify: `application/app/Application/Quality/ResponseReviewQuery.php`
- Modify: `application/app/Application/Quality/ReviewSubmission.php`
- Modify: `application/app/Application/Calculation/CalculationInputSnapshot.php`
- Modify: `application/app/Livewire/Admin/Responses.php`
- Modify: `application/resources/views/livewire/admin/responses.blade.php`
- Test: `application/tests/Feature/Quality/QualityFlaggerTest.php`
- Test: `application/tests/Feature/Survey/SubmitSurveyTest.php`
- Test: `application/tests/Feature/Admin/ResponsesTest.php`
- Test: `application/tests/Feature/Calculation/CalculationRunServiceTest.php`

**Interfaces:**
- Consumes: submission lengkap dengan 26 answers dan konfigurasi quality rule periode yang telah dikunci.
- Produces: `InitializeQualityReview::handle(SurveySubmission): QualityReview` dengan `flags` terisi dan keputusan/reviewer/waktu masih null.
- Preserves: `ReviewSubmission::handle(...)` tetap menjadi satu-satunya jalan menetapkan `included` atau `excluded`.

- [x] **Step 1: Write failing schema and submission tests**

```php
it('persists prospective flags in the survey transaction before review', function () {
    $fixture = surveyFixture();
    $data = validSubmitSurveyData($fixture);

    $submission = app(SubmitSurvey::class)->handle($data);
    $review = $submission->fresh('qualityReview')->qualityReview;

    expect($review)->not->toBeNull()
        ->and($review->flags)->toBe([
            'fast_completion' => false,
            'identical_answers' => true,
        ])
        ->and($review->decision)->toBeNull()
        ->and($review->reviewed_by)->toBeNull()
        ->and($review->reviewed_at)->toBeNull();
});

it('shows automatic flags without pretending the response was reviewed', function () {
    $fixture = completedSubmissionFixture();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)->test(Responses::class)
        ->assertSee('Jawaban identik')
        ->assertSee('Belum direview');
});
```

- [x] **Step 2: Run the tests and verify the intended failure**

Run:

```bash
cd application
php artisan test tests/Feature/Survey/SubmitSurveyTest.php tests/Feature/Quality/QualityFlaggerTest.php tests/Feature/Admin/ResponsesTest.php
```

Expected: FAIL because `quality_reviews.decision`, `reviewed_by`, and `reviewed_at` reject null and submit does not initialize flags.

- [x] **Step 3: Make pending reviews represent automatic flags explicitly**

Migration shape:

```php
Schema::table('quality_reviews', function (Blueprint $table): void {
    $table->string('decision')->nullable()->change();
    $table->foreignId('reviewed_by')->nullable()->change();
    $table->timestamp('reviewed_at')->nullable()->change();
});
```

Initializer:

```php
final class InitializeQualityReview
{
    public function __construct(private readonly QualityFlagger $flagger) {}

    public function handle(SurveySubmission $submission): QualityReview
    {
        return $submission->qualityReview()->updateOrCreate([], [
            'flags' => $this->flagger->for($submission),
            'decision' => null,
            'reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }
}
```

Inject `InitializeQualityReview` into `SubmitSurvey`. Call it after `answers()->createMany(...)` and before updating the session so submission, answers, and flags commit or roll back together. Make `QualityReview::$decision` nullable in PHPDoc and use `$review?->decision?->value` in `ResponseReviewQuery`, `Responses::openReview`, and `CalculationInputSnapshot`. A pending row maps to the snapshot decision string `unreviewed`. `ReviewSubmission` must retain the existing flags instead of silently changing prospective rules during manual review.

- [x] **Step 4: Render stable Indonesian labels and verify no sensitive data is exposed**

Use an explicit map in the Blade view:

```php
@php($flagLabels = [
    'fast_completion' => 'Durasi terlalu cepat',
    'identical_answers' => 'Jawaban identik',
])
```

Assert the page does not render `token_hash`, anonymous respondent ID, cookie, IP address, or user agent.

- [x] **Step 5: Run focused regression and commit**

```bash
cd application
php artisan test tests/Feature/Survey/SubmitSurveyTest.php tests/Feature/Quality tests/Feature/Admin/ResponsesTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
vendor/bin/phpstan analyse --memory-limit=512M
git add database/migrations/2026_08_06_000017_allow_pending_quality_reviews.php app/Application/Quality app/Application/Survey/SubmitSurvey.php app/Application/Calculation/CalculationInputSnapshot.php app/Livewire/Admin/Responses.php app/Models/QualityReview.php resources/views/livewire/admin/responses.blade.php tests/Feature/Quality tests/Feature/Survey/SubmitSurveyTest.php tests/Feature/Admin/ResponsesTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
git commit -m "fix: persist prospective response quality flags"
```

Expected: focused tests pass and PHPStan reports zero errors.

## Task 2: Centralize calculation-input revision, stale lifecycle, and audit

**Files:**
- Create: `application/app/Application/Calculation/CalculationInputChangeRecorder.php`
- Modify: `application/app/Application/Quality/ReviewSubmission.php`
- Modify: `application/app/Models/AuditEvent.php`
- Test: `application/tests/Feature/Calculation/CalculationInputChangeRecorderTest.php`
- Test: `application/tests/Feature/Quality/ReviewSubmissionTest.php`

**Interfaces:**
- Consumes: period, actor, action, auditable model identity, old values, and new values from a transaction-owning application service.
- Produces: one incremented `calculation_input_revision`, all current preview runs marked `stale`, and one append-only `audit_events` row.
- Produces method: `CalculationInputChangeRecorder::record(EvaluationPeriod, User, string, string, int, ?array, array): void`.

- [x] **Step 1: Write a failing atomic lifecycle test**

```php
it('increments revision, stales previews, and appends an audit event', function () {
    $period = EvaluationPeriod::factory()->create(['calculation_input_revision' => 4]);
    $actor = User::factory()->create();
    $run = CalculationRun::query()->create([
        'evaluation_period_id' => $period->id,
        'algorithm_version' => CalculationRunService::ALGORITHM_VERSION,
        'status' => 'preview',
        'input_hash' => str_repeat('a', 64),
        'input_snapshot' => [],
        'warnings' => [],
        'included_count' => 0,
        'excluded_count' => 0,
        'created_by' => $actor->id,
        'calculated_at' => now(),
    ]);

    app(CalculationInputChangeRecorder::class)->record(
        $period,
        $actor,
        'technical_assessment.updated',
        TechnicalInformant::class,
        27,
        ['anonymous_code' => 'TI-01'],
        ['anonymous_code' => 'TI-01', 'weights' => ['c1' => 40, 'c2' => 30, 'c3' => 30]],
    );

    expect($period->fresh()->calculation_input_revision)->toBe(5)
        ->and($run->fresh()->status)->toBe('stale');
    $this->assertDatabaseHas('audit_events', [
        'action' => 'technical_assessment.updated',
        'actor_id' => $actor->id,
    ]);
});
```

- [x] **Step 2: Run the test and verify failure**

```bash
cd application
php artisan test tests/Feature/Calculation/CalculationInputChangeRecorderTest.php
```

Expected: FAIL because the recorder does not exist.

- [x] **Step 3: Implement one mutation recorder**

```php
final class CalculationInputChangeRecorder
{
    public function record(
        EvaluationPeriod $period,
        User $actor,
        string $action,
        string $auditableType,
        int $auditableId,
        ?array $oldValues,
        array $newValues,
    ): void {
        $lockedPeriod = EvaluationPeriod::query()->lockForUpdate()->findOrFail($period->id);
        $lockedPeriod->increment('calculation_input_revision');

        CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->where('status', 'preview')
            ->update(['status' => 'stale']);

        AuditEvent::query()->create([
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'actor_id' => $actor->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
```

The recorder must be called inside the same `DB::transaction` that changes the input. Refactor `ReviewSubmission` to call it after its review row is created/updated and remove its duplicated period/run/audit statements. Official and archived runs are historical records and must not be rewritten by this Rilis 2 mutation service.

- [x] **Step 4: Prove rollback is atomic**

Add a test that throws after `record(...)` inside `DB::transaction`; assert revision, run status, and audit count remain unchanged after rollback.

- [x] **Step 5: Run focused verification and commit**

```bash
cd application
php artisan test tests/Feature/Calculation/CalculationInputChangeRecorderTest.php tests/Feature/Quality/ReviewSubmissionTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
git add app/Application/Calculation/CalculationInputChangeRecorder.php app/Application/Quality/ReviewSubmission.php app/Models/AuditEvent.php tests/Feature/Calculation/CalculationInputChangeRecorderTest.php tests/Feature/Quality/ReviewSubmissionTest.php
git commit -m "fix: centralize calculation input lifecycle"
```

Expected: revision, stale, audit, and rollback assertions pass.

## Task 3: Enforce 3-5 complete technical informants and calculate sample SD

**Files:**
- Create: `application/app/Domain/Technical/TechnicalConsensusData.php`
- Create: `application/app/Domain/Technical/TechnicalUnitConsensus.php`
- Create: `application/tests/Support/ReleaseTwoFixture.php`
- Modify: `application/app/Domain/Technical/SaveTechnicalAssessment.php`
- Modify: `application/app/Domain/Technical/TechnicalConsensus.php`
- Modify: `application/app/Livewire/Admin/TechnicalAssessments.php`
- Modify: `application/resources/views/livewire/admin/technical-assessments.blade.php`
- Modify: `application/app/Application/Calculation/CalculationInputSnapshot.php`
- Modify: `application/app/Application/Calculation/SawResultWriter.php`
- Test: `application/tests/Unit/Technical/TechnicalConsensusTest.php`
- Test: `application/tests/Feature/Admin/TechnicalAssessmentsTest.php`
- Test: `application/tests/Feature/Calculation/TechnicalInputLifecycleTest.php`

**Interfaces:**
- Consumes: exactly 13 keyed assessments, weights, anonymous code, period, and actor.
- Produces save signature: `SaveTechnicalAssessment::handle(EvaluationPeriod, string, array, array, User): TechnicalInformant`.
- Produces consensus: `TechnicalConsensus::for(EvaluationPeriod): TechnicalConsensusData`.
- `TechnicalConsensusData` fields: `informantCount`, `isComplete`, `incompleteReasons`, `units`, and normalized `weights`.
- `TechnicalUnitConsensus` fields: `unitId`, `n`, `meanDays`, `standardDeviationDays`, `meanUrgency`, and `standardDeviationUrgency`.
- Test support: `ReleaseTwoFixture::completeAssessments(float $days = 1.0, int $urgency = 3): array`, `ReleaseTwoFixture::saveInformant(EvaluationPeriod, User, string, float, int, array): TechnicalInformant`, `ReleaseTwoFixture::seedInformants(EvaluationPeriod, User, int): Collection`, dan `ReleaseTwoFixture::scenario(): object`.
- `ReleaseTwoFixture::scenario()` seeds `WongReangStudySeeder`, verifies all six benchmarks, creates two varied included submissions for each of the first two units, creates three complete informants over all 13 units, and returns an object with `period` plus `admin`. Use the two answer vectors already proven in `tests/Feature/Admin/CalculationsTest.php:54-63`; do not invent a third numeric oracle.

- [x] **Step 1: Write failing domain-boundary tests**

```php
it('rejects a partial informant before any row is written', function () {
    $period = EvaluationPeriod::factory()->create();
    $actor = User::factory()->create();
    $assessments = ReleaseTwoFixture::completeAssessments();
    array_pop($assessments);

    expect(fn () => app(SaveTechnicalAssessment::class)->handle(
        $period,
        'TI-01',
        $assessments,
        ['c1' => 40, 'c2' => 30, 'c3' => 30],
        $actor,
    ))->toThrow(DomainException::class, 'tepat 13 modul');

    expect(TechnicalInformant::count())->toBe(0);
});

it('rejects a sixth informant but permits updating an existing code', function () {
    ReleaseTwoFixture::seedInformants($this->period, $this->admin, 5);

    expect(fn () => ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-06', 1.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 30]))
        ->toThrow(DomainException::class, 'maksimal lima informan');

    expect(fn () => ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-05', 1.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 30]))
        ->not->toThrow(DomainException::class);
});

it('rejects invalid values through the domain service', function (float $days, int $urgency, array $weights) {
    $assessments = ReleaseTwoFixture::completeAssessments(days: $days, urgency: $urgency);

    expect(fn () => app(SaveTechnicalAssessment::class)->handle(
        $this->period,
        'TI-01',
        $assessments,
        $weights,
        $this->admin,
    ))->toThrow(DomainException::class);
})->with([
    'zero days' => [0.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 30]],
    'urgency six' => [1.0, 6, ['c1' => 40, 'c2' => 30, 'c3' => 30]],
    'weight ninety' => [1.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 20]],
]);
```

- [x] **Step 2: Run focused tests and verify failure**

```bash
cd application
php artisan test tests/Unit/Technical/TechnicalConsensusTest.php tests/Feature/Admin/TechnicalAssessmentsTest.php tests/Feature/Calculation/TechnicalInputLifecycleTest.php
```

Expected: FAIL because the domain accepts partial/invalid input, has no five-informant cap, and returns an untyped object without SD/completeness.

- [x] **Step 3: Implement domain validation before opening the transaction**

Validation rules in `SaveTechnicalAssessment`:

```php
$anonymousCode = trim($anonymousCode);
throw_if($anonymousCode === '' || mb_strlen($anonymousCode) > 100, DomainException::class, 'Kode anonim informan wajib diisi dan maksimal 100 karakter.');

$fixedUnitIds = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->pluck('id')->all();
$providedUnitIds = array_map('intval', array_keys($assessments));
sort($fixedUnitIds);
sort($providedUnitIds);
throw_unless($providedUnitIds === $fixedUnitIds, DomainException::class, 'Setiap informan harus menilai tepat 13 modul Wong Reang.');

foreach ($assessments as $assessment) {
    throw_unless(is_numeric($assessment['days']) && (float) $assessment['days'] > 0, DomainException::class, 'Estimasi hari harus lebih dari nol.');
    throw_unless(is_int($assessment['urgency']) && $assessment['urgency'] >= 1 && $assessment['urgency'] <= 5, DomainException::class, 'Urgensi arsitektur harus bilangan bulat 1 sampai 5.');
}

$weightKeys = array_keys($weights);
sort($weightKeys);
throw_unless($weightKeys === ['c1', 'c2', 'c3'], DomainException::class, 'Bobot harus memuat C1, C2, dan C3.');
throw_unless(collect($weights)->every(fn ($value) => is_int($value) && $value >= 0 && $value <= 100), DomainException::class, 'Setiap bobot harus bilangan bulat 0 sampai 100.');
throw_unless(array_sum($weights) === 100, DomainException::class, 'Total bobot C1, C2, dan C3 harus tepat 100 poin.');
```

Implement the shared fixture methods as thin calls to the public service, never by inserting technical rows directly:

```php
final class ReleaseTwoFixture
{
    public static function completeAssessments(float $days = 1.0, int $urgency = 3): array
    {
        return EvaluationUnit::query()->forWongReang()->orderBy('display_order')->get()
            ->mapWithKeys(fn (EvaluationUnit $unit): array => [
                $unit->id => ['days' => $days, 'urgency' => $urgency],
            ])->all();
    }

    public static function saveInformant(
        EvaluationPeriod $period,
        User $actor,
        string $code,
        float $days = 1.0,
        int $urgency = 3,
        array $weights = ['c1' => 40, 'c2' => 30, 'c3' => 30],
    ): TechnicalInformant {
        return app(SaveTechnicalAssessment::class)->handle(
            $period,
            $code,
            self::completeAssessments($days, $urgency),
            $weights,
            $actor,
        );
    }
}
```

`seedInformants` calls `saveInformant` for `TI-01` through `TI-0N`. `scenario` uses application services for submissions and reviews, then calls `seedInformants(..., 3)`; direct model inserts are prohibited so the same validation/audit path is exercised.

Before creating a new code, lock the period and count its informants; reject count `>= 5`. After persistence, call `CalculationInputChangeRecorder` with individual old/new assessments and weights. Update the Livewire call to pass `auth()->user()` and make every unit required rather than nullable.

- [x] **Step 4: Return deterministic mean, sample SD, completeness, and normalized weights**

`TechnicalUnitConsensus` must use sample SD:

```php
private function sampleStandardDeviation(array $values): ?float
{
    $n = count($values);
    if ($n < 2) {
        return null;
    }

    $mean = array_sum($values) / $n;
    $sum = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $values));

    return sqrt($sum / ($n - 1));
}
```

`isComplete` is true only when informant count is 3-5, every informant has exactly 13 assessments and one weight row, every unit has `n === informantCount`, and final normalized weights sum to one within `0.000001`. Add the complete consensus to `CalculationInputSnapshot`:

```php
'technical_consensus' => $consensus->toArray(),
```

Make `SawResultWriter` return no rows plus one explicit warning when `technical_consensus.is_complete` is false. It must consume the snapshot means, not re-average arbitrary available rows.

- [x] **Step 5: Render evidence and verify stale lifecycle**

The technical page must display informant count, complete/incomplete status, per-unit `n`, mean days, SD days, mean urgency, and SD urgency. Add a feature test that creates a preview, updates one existing informant, and asserts the old preview becomes stale and one `technical_assessment.updated` audit event exists.

- [x] **Step 6: Run focused regression and commit**

```bash
cd application
php artisan test tests/Unit/Technical tests/Feature/Admin/TechnicalAssessmentsTest.php tests/Feature/Calculation/TechnicalInputLifecycleTest.php tests/Unit/Saw/SawCalculatorTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
vendor/bin/phpstan analyse --memory-limit=512M
git add app/Domain/Technical app/Livewire/Admin/TechnicalAssessments.php resources/views/livewire/admin/technical-assessments.blade.php app/Application/Calculation/CalculationInputSnapshot.php app/Application/Calculation/SawResultWriter.php tests/Support/ReleaseTwoFixture.php tests/Unit/Technical tests/Feature/Admin/TechnicalAssessmentsTest.php tests/Feature/Calculation/TechnicalInputLifecycleTest.php
git commit -m "fix: enforce complete technical informant consensus"
```

Expected: partial/invalid/sixth informant tests fail safely; 3-5 complete informants produce mean, sample SD, normalized weights, and eligible SAW input.

## Task 4: Separate descriptive availability from reliability and persist pooled alpha

**Files:**
- Create: `application/database/migrations/2026_08_06_000018_create_ueq_pooled_results_and_reliability_metadata.php`
- Create: `application/app/Models/UeqPooledResult.php`
- Modify: `application/app/Models/UeqResult.php`
- Modify: `application/app/Models/CalculationRun.php`
- Modify: `application/app/Domain/Ueq/UeqScaleStatistics.php`
- Modify: `application/app/Domain/Ueq/UeqStatisticsCalculator.php`
- Modify: `application/app/Application/Calculation/UeqResultWriter.php`
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Test: `application/tests/Unit/Ueq/UeqStatisticsCalculatorTest.php`
- Test: `application/tests/Feature/Calculation/UeqReliabilityPersistenceTest.php`

**Interfaces:**
- Consumes: ordered item mapping plus raw answers included for one unit or pooled across units.
- Produces: available mean whenever `n >= 1`; SD/SE/CI when `n >= 2`; alpha when item/total variance permits.
- Produces: `UeqScaleStatistics::$reliabilityUnavailableReason` and `UeqScaleStatistics::$reliabilityWarnings` independently from descriptive `unavailableReason`.
- Produces relation: `CalculationRun::ueqPooledResults(): HasMany`.
- Produces writer result: `array{rows: list<array<string,mixed>>, pooledRows: list<array<string,mixed>>, warnings: list<string>}`; `CalculationRunService` passes both collections to persistence in the same transaction.

- [x] **Step 1: Write failing statistic-specific availability tests**

```php
it('keeps a one-response mean while marking inferential statistics unavailable', function () {
    $fixture = ueqGoldenFixture();
    $answers = [includedAnswersForUnit($fixture, 'ibadah-yu')[0]];

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->n)->toBe(1)
        ->and($result->mean)->not->toBeNull()
        ->and($result->standardDeviation)->toBeNull()
        ->and($result->unavailableReason)->toBe('n_below_2')
        ->and($result->reliabilityUnavailableReason)->toBe('n_below_2');
});

it('keeps zero descriptive variance as zero but leaves alpha unavailable', function () {
    $fixture = ueqGoldenFixture();
    $answers = array_fill(0, 3, array_fill_keys(range(1, 26), 4));

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->mean)->toBe(0.0)
        ->and($result->standardDeviation)->toBe(0.0)
        ->and($result->standardError)->toBe(0.0)
        ->and($result->ci95Lower)->toBe(0.0)
        ->and($result->ci95Upper)->toBe(0.0)
        ->and($result->cronbachAlpha)->toBeNull()
        ->and($result->reliabilityUnavailableReason)->toBe('zero_total_variance');
});
```

- [x] **Step 2: Write failing pooled and warning persistence tests**

```php
it('stores six pooled diagnostics and unit reliability warnings', function () {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);

    expect($run->ueqPooledResults)->toHaveCount(6)
        ->and($run->ueqPooledResults->pluck('scope')->unique()->all())->toBe(['pooled'])
        ->and($run->ueqResults->every(fn (UeqResult $row) => is_array($row->reliability_warnings)))->toBeTrue();

    expect($run->ueqResults->firstWhere('n', 4)->reliability_warnings)
        ->toContain('n_below_20');
});
```

- [x] **Step 3: Run tests and verify failure**

```bash
cd application
php artisan test tests/Unit/Ueq/UeqStatisticsCalculatorTest.php tests/Feature/Calculation/UeqReliabilityPersistenceTest.php
```

Expected: FAIL because current statistics collapse zero variance to a fully unavailable row and pooled storage does not exist.

- [x] **Step 4: Add reliability persistence**

Migration fields:

```php
Schema::table('ueq_results', function (Blueprint $table): void {
    $table->string('reliability_unavailable_reason')->nullable()->after('cronbach_alpha');
    $table->json('reliability_warnings')->after('reliability_unavailable_reason');
});

Schema::create('ueq_pooled_results', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('calculation_run_id')->constrained()->restrictOnDelete();
    $table->string('scope')->default('pooled');
    $table->string('scale');
    $table->unsignedInteger('n');
    $table->decimal('cronbach_alpha', 18, 10)->nullable();
    $table->string('unavailable_reason')->nullable();
    $table->json('warnings');
    $table->timestamps();
    $table->unique(['calculation_run_id', 'scope', 'scale']);
});
```

Warnings are deterministic:

```php
$warnings = [];
if ($n < 20) {
    $warnings[] = 'n_below_20';
}
if ($cronbachAlpha !== null && $cronbachAlpha < 0.70) {
    $warnings[] = 'alpha_below_0_70';
}
```

For pooled rows, do not add `n_below_20` based on a unit threshold; label scope `pooled`, keep `alpha_below_0_70` when applicable, and show that it is diagnostic. Compute pooled results from all included module evaluations in the snapshot, not by averaging unit alpha values.

- [x] **Step 5: Preserve golden mathematics and commit**

```bash
cd application
php artisan test tests/Unit/Ueq tests/Feature/Calculation/UeqReliabilityPersistenceTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
vendor/bin/phpstan analyse --memory-limit=512M
git add database/migrations/2026_08_06_000018_create_ueq_pooled_results_and_reliability_metadata.php app/Models/UeqPooledResult.php app/Models/UeqResult.php app/Models/CalculationRun.php app/Domain/Ueq app/Application/Calculation/UeqResultWriter.php app/Application/Calculation/CalculationRunService.php tests/Unit/Ueq tests/Feature/Calculation/UeqReliabilityPersistenceTest.php
git commit -m "feat: persist complete UEQ reliability diagnostics"
```

Expected: all existing golden values remain within `0.000001`, descriptive values remain available when mathematically defined, and every run has six pooled diagnostics.

## Task 5: Make every Rilis 2 result and calculation action auditable and immutable

**Files:**
- Modify: `application/app/Models/SawResult.php`
- Modify: `application/app/Models/UeqPooledResult.php`
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Test: `application/tests/Feature/Calculation/CalculationResultImmutabilityTest.php`
- Test: `application/tests/Feature/Calculation/CalculationRunServiceTest.php`

**Interfaces:**
- Consumes: completed calculation rows and actor.
- Produces: immutable UEQ, pooled, and SAW rows plus `calculation_run.created` audit event.
- Preserves: status-only transitions allowed by `CalculationRun`; numeric result fields never update in place.

- [x] **Step 1: Write failing immutability and audit tests**

```php
it('prevents SAW and pooled result mutation or deletion', function () {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);

    expect(fn () => $run->sawResults->first()->update(['preference_value' => 0.0]))
        ->toThrow(LogicException::class, 'SAW results are immutable.');
    expect(fn () => $run->ueqPooledResults->first()->delete())
        ->toThrow(LogicException::class, 'UEQ pooled results are immutable.');
});

it('audits calculation creation without copying raw answers into the audit event', function () {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);
    $audit = AuditEvent::query()->where('action', 'calculation_run.created')->sole();

    expect($audit->auditable_id)->toBe($run->id)
        ->and($audit->actor_id)->toBe($this->admin->id)
        ->and($audit->new_values)->toMatchArray([
            'algorithm_version' => $run->algorithm_version,
            'input_hash' => $run->input_hash,
            'status' => $run->status,
        ])
        ->and(json_encode($audit->new_values))->not->toContain('included_raw_answers');
});
```

- [x] **Step 2: Run tests and verify failure**

```bash
cd application
php artisan test tests/Feature/Calculation/CalculationResultImmutabilityTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
```

Expected: FAIL because `SawResult` is mutable and calculation creation has no audit event.

- [x] **Step 3: Mirror the existing UEQ immutability guard**

In both result models:

```php
protected static function booted(): void
{
    static::updating(fn () => throw new LogicException('SAW results are immutable.'));
    static::deleting(fn () => throw new LogicException('SAW results are immutable.'));
}
```

Use the model-specific message for pooled rows. Cast every SAW decimal to `decimal:10`, `rank` to integer, and `is_tied` to boolean so UI/export and snapshot comparisons do not depend on driver-specific strings.

- [x] **Step 4: Append a calculation audit event in the calculation transaction**

After all result writers finish, create:

```php
AuditEvent::query()->create([
    'action' => 'calculation_run.created',
    'auditable_type' => CalculationRun::class,
    'auditable_id' => $run->id,
    'actor_id' => $actor->id,
    'old_values' => null,
    'new_values' => [
        'algorithm_version' => $run->algorithm_version,
        'input_hash' => $run->input_hash,
        'status' => $run->status,
        'included_count' => $run->included_count,
        'excluded_count' => $run->excluded_count,
        'calculated_at' => $run->calculated_at->toIso8601String(),
    ],
]);
```

Do not duplicate the input snapshot in `audit_events`; the run already owns it.

- [x] **Step 5: Verify and commit**

```bash
cd application
php artisan test tests/Feature/Calculation/CalculationResultImmutabilityTest.php tests/Feature/Calculation/CalculationRunServiceTest.php tests/Feature/Calculation/OfficialRunLockTest.php
git add app/Models/SawResult.php app/Models/UeqPooledResult.php app/Application/Calculation/CalculationRunService.php tests/Feature/Calculation/CalculationResultImmutabilityTest.php tests/Feature/Calculation/CalculationRunServiceTest.php
git commit -m "fix: make calculation evidence auditable and immutable"
```

Expected: direct model updates/deletes fail, creation is audited, and official-lock regression remains green.

## Task 6: Turn the golden workbook into a complete automated oracle

**Files:**
- Create: `application/tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php`
- Create: `application/tests/Unit/Saw/SawGoldenFixtureTest.php`
- Create: `application/tests/Feature/Calculation/GoldenCalculationRunTest.php`
- Create: `application/tests/Support/GoldenFixture.php`
- Use unchanged oracle: `docs/research/ueq-saw-golden-fixture.xlsx`

**Interfaces:**
- Consumes: formula-backed XLSX and JSON fixture version `ueq-saw-v1` with tolerance `0.000001`.
- Produces: automated evidence for all six Rilis 2 acceptance criteria, including gap, X, R, contributions, Vi, rank, and tie.
- Test support: `GoldenFixture::data(): array`, `GoldenFixture::sawRows(): array`, dan `GoldenFixture::persistedRun(): CalculationRun`. `data()` reads `tests/Fixtures/ueq-saw-golden.json`; `sawRows()` calls `UeqStatisticsCalculator` and `SawCalculator`; `persistedRun()` creates database rows through survey/review/technical services and calls `CalculationRunService::preview`.

- [x] **Step 1: Write a failing workbook-to-JSON consistency test**

```php
it('keeps the machine-readable fixture equal to the independent workbook', function () {
    $json = GoldenFixture::data();
    $book = IOFactory::load(base_path('../docs/research/ueq-saw-golden-fixture.xlsx'));
    $overview = $book->getSheetByName('Overview');
    $saw = $book->getSheetByName('Technical and SAW');

    expect((float) $overview->getCell('D14')->getCalculatedValue())
        ->toEqualWithDelta($json['expected']['ueq']['ibadah-yu']['Attractiveness']['mean'], $json['tolerance'])
        ->and((float) $overview->getCell('J14')->getCalculatedValue())
        ->toEqualWithDelta($json['expected']['gaps']['ibadah-yu']['Attractiveness'], $json['tolerance'])
        ->and((float) $saw->getCell('K15')->getCalculatedValue())
        ->toEqualWithDelta($json['expected']['saw']['ibadah-yu']['vi'], $json['tolerance']);
});
```

Extend the test with table-driven cell maps for all 12 unit-scale rows, six gap values per unit, three weights, two X/R/contribution/Vi rows, and both tie/rank outcomes. Assert the workbook contains formulas in transformed scores, respondent scale means, overview statistics, and technical/SAW sheets.

- [x] **Step 2: Write the full SAW golden test**

```php
it('matches every golden SAW intermediate and final value', function () {
    $fixture = GoldenFixture::data();
    $rows = GoldenFixture::sawRows();

    foreach ($rows as $row) {
        $expected = $fixture['expected']['saw'][$row->alternative->unitCode];
        expect($row->alternative->gap)->toEqualWithDelta($expected['x1_gap'], $fixture['tolerance'])
            ->and($row->r1)->toEqualWithDelta($expected['r1'], $fixture['tolerance'])
            ->and($row->r2)->toEqualWithDelta($expected['r2'], $fixture['tolerance'])
            ->and($row->r3)->toEqualWithDelta($expected['r3'], $fixture['tolerance'])
            ->and($row->contributionC1)->toEqualWithDelta($expected['contribution_c1'], $fixture['tolerance'])
            ->and($row->contributionC2)->toEqualWithDelta($expected['contribution_c2'], $fixture['tolerance'])
            ->and($row->contributionC3)->toEqualWithDelta($expected['contribution_c3'], $fixture['tolerance'])
            ->and($row->preferenceValue)->toEqualWithDelta($expected['vi'], $fixture['tolerance'])
            ->and($row->rank)->toBe($expected['rank'])
            ->and($row->isTied)->toBe($expected['is_tied']);
    }
});
```

- [x] **Step 3: Write the end-to-end calculation persistence test**

Implement `GoldenFixture::persistedRun()` by seeding fixture submissions and decisions, three complete informants, verified benchmarks, and 13 fixed units. Untuk sebelas unit non-oracle, isi setiap informan dengan `days = 1.0` dan `urgency = 1`; nilai tersebut hanya memenuhi kontrak kelengkapan dan tidak menghasilkan SAW row karena tidak ada included UEQ submission pada unit itu. Then assert the returned run:

```php
$run = GoldenFixture::persistedRun();

expect($run->input_hash)->toHaveLength(64)
    ->and($run->included_count)->toBe(8)
    ->and($run->excluded_count)->toBe(2)
    ->and($run->sawResults)->toHaveCount(2)
    ->and($run->ueqPooledResults)->toHaveCount(6);

foreach ($fixture['expected']['gaps'] as $unitCode => $expectedScales) {
    foreach ($expectedScales as $scale => $expectedGap) {
        $persisted = $run->ueqResults
            ->first(fn (UeqResult $row) => $row->unit->code === $unitCode && $row->scale === $scale);
        expect((float) $persisted->gap)->toEqualWithDelta($expectedGap, $fixture['tolerance']);
    }
}
```

Also assert snapshot contains item polarity, benchmark source/version/threshold, individual technical values, technical consensus with SD, quality decisions, included raw answers, and final weights.

- [x] **Step 4: Run oracle verification and commit**

```bash
cd application
php artisan test tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php tests/Unit/Ueq tests/Unit/Saw/SawGoldenFixtureTest.php tests/Feature/Calculation/GoldenCalculationRunTest.php
git add tests/Support/GoldenFixture.php tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php tests/Unit/Saw/SawGoldenFixtureTest.php tests/Feature/Calculation/GoldenCalculationRunTest.php
git commit -m "test: verify release two against golden workbook"
```

Expected: workbook and JSON agree within `0.000001`; persisted calculation results have no mismatch.

## Task 7: Complete the admin evidence tables without regressing Rilis 3

**Files:**
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Modify: `application/resources/views/livewire/admin/technical-assessments.blade.php`
- Modify: `application/resources/views/livewire/admin/responses.blade.php`
- Test: `application/tests/Feature/Admin/CalculationsTest.php`
- Test: `application/tests/Feature/Admin/TechnicalAssessmentsTest.php`
- Test: `application/tests/Feature/Admin/ResponsesTest.php`
- Regression: `application/tests/Feature/Admin/ReportsTest.php`
- Regression: `application/tests/Feature/Admin/ExpertJudgmentTest.php`

**Interfaces:**
- Consumes: persisted run relations and benchmark/consensus metadata from `input_snapshot`.
- Produces: tables that expose every Rilis 2 intermediate without exposing raw answers or respondent identity.
- Preserves: sensitivity, expert judgment, official lock, and aggregate-report controls.

- [x] **Step 1: Write failing UI contract tests**

```php
it('shows complete run, UEQ, reliability, and SAW evidence', function () {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);

    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $run->evaluation_period_id])
        ->set('runId', $run->id)
        ->assertSee('Dibuat oleh')
        ->assertSee('CI 95% bawah')
        ->assertSee('Batas Good')
        ->assertSee('Pooled reliability')
        ->assertSee('Kontribusi C1')
        ->assertSee('Kontribusi C2')
        ->assertSee('Kontribusi C3')
        ->assertDontSee('included_raw_answers');
});
```

- [x] **Step 2: Run tests and verify failure**

```bash
cd application
php artisan test tests/Feature/Admin/CalculationsTest.php tests/Feature/Admin/TechnicalAssessmentsTest.php tests/Feature/Admin/ResponsesTest.php
```

Expected: FAIL because creator/time, CI, benchmark, pooled rows, SD teknis, and SAW contributions are not all rendered.

- [x] **Step 3: Add explicit view data and columns**

Eager-load `creator` and `ueqPooledResults`. Build benchmark lookup only from the selected run snapshot:

```php
$benchmarkByScale = collect($run?->input_snapshot['benchmarks'] ?? [])
    ->keyBy('scale')
    ->map(fn (array $row): string => $row['good_threshold']);
```

UEQ table columns: unit, scale, n, mean, SD, SE, CI lower, CI upper, alpha, reliability warning, Good threshold, gap, and unavailable reason. Add a separate pooled table with scale, pooled n, alpha, warning, and unavailable reason.

SAW table columns: rank/tie, unit, X1-X3, R1-R3, contribution C1-C3, and Vi. Technical table columns: unit, n informan, mean/SD days, mean/SD urgency, plus completeness reasons. Run metadata: ID, algorithm version, status, creator, calculated time, input hash, included/excluded counts, and warnings.

- [x] **Step 4: Verify privacy and Rilis 3 regression**

Assert calculations, responses, and technical pages do not display token hash, anonymous respondent ID, raw answers, cookies, IP, or user agent. Assert sensitivity, expert judgment, report, and official lock text still render for eligible Rilis 3 fixtures.

- [x] **Step 5: Run focused verification and commit**

```bash
cd application
php artisan test tests/Feature/Admin/CalculationsTest.php tests/Feature/Admin/TechnicalAssessmentsTest.php tests/Feature/Admin/ResponsesTest.php tests/Feature/Admin/ReportsTest.php tests/Feature/Admin/ExpertJudgmentTest.php
npm run build
git add app/Livewire/Admin/Calculations.php resources/views/livewire/admin/calculations.blade.php resources/views/livewire/admin/technical-assessments.blade.php resources/views/livewire/admin/responses.blade.php tests/Feature/Admin
git commit -m "feat: expose complete release two calculation evidence"
```

Expected: all numeric evidence is visible in tables, no sensitive fields render, Rilis 3 controls remain available, and Vite exits zero.

## Task 8: Close browser UAT, MySQL evidence, and the release runbook

**Files:**
- Create: `application/tests/Browser/AdminAnalysisFlowTest.php`
- Modify: `application/tests/Browser/ReleaseThreeFlowTest.php`
- Modify: `application/docs/release-2-runbook.md`
- Modify: `docs/superpowers/plans/2026-08-06-ueq-saw-release-2-gap-remediation.md`

**Interfaces:**
- Consumes: Tasks 1-7, MySQL runtime, explicit 2FA-authenticated browser session, and fixture-generated non-secret data.
- Produces: reproducible pass/fail evidence for every Rilis 2 acceptance criterion.

- [x] **Step 1: Write the browser flow with unique selectors**

```php
it('reviews quality, records three informants, and opens a traceable preview', function () {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.dashboard'))
        ->resize(1280, 800)
        ->assertSee('Dashboard progres')
        ->click('[data-flux-sidebar-item][href$="/admin/responses"]')
        ->waitForText('Review kualitas respons')
        ->assertSee('Jawaban identik')
        ->click('[data-flux-sidebar-item][href$="/admin/technical-assessments"]')
        ->waitForText('Informan teknis')
        ->assertSee('3 informan lengkap')
        ->click('[data-flux-sidebar-item][href$="/admin/calculations"]')
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Input hash')
        ->assertSee('Pooled reliability')
        ->assertSee('Kontribusi C1');
});
```

Use test-created authentication state; do not put credentials, tokens, cookies, or respondent identifiers in source or screenshots.

- [x] **Step 2: Repair the stale Rilis 3 selector and prove the failure is gone**

Replace the ambiguous selector in `ReleaseThreeFlowTest`:

```php
->click('[data-flux-sidebar-item][href$="/admin/reports"]')
```

Run:

```bash
cd application
php artisan test tests/Browser/AdminAnalysisFlowTest.php tests/Browser/ReleaseThreeFlowTest.php
```

Expected: both browser tests pass at 1280x800. No screenshot is created under `tests/Browser/Screenshots`.

- [x] **Step 3: Verify MySQL shape without exposing secrets**

Run against the configured research database after confirming the environment name and database driver:

```bash
cd application
php artisan about --only=environment
php artisan migrate:status
php artisan tinker --execute="dump(['periods' => App\\Models\\EvaluationPeriod::count(), 'units' => App\\Models\\EvaluationUnit::count(), 'items' => App\\Models\\UeqItem::count(), 'benchmarks' => App\\Models\\UeqBenchmark::count(), 'verified_benchmarks' => App\\Models\\UeqBenchmark::query()->whereNotNull('verified_at')->count()]);"
```

Expected: MySQL driver, all migrations ran, one study period, 13 units, 26 current-version items, and six verified benchmarks. Apply pending migrations with `php artisan migrate --force` only after confirming the target database is the intended research environment and a recoverable backup exists.

- [x] **Step 4: Execute the complete gates**

```bash
cd application
composer test
php artisan test tests/Browser/AdminAnalysisFlowTest.php tests/Browser/ReleaseThreeFlowTest.php tests/Browser/AdminSidebarTest.php
npm run build
git diff --check
```

Expected: Pint passes, PHPStan reports zero errors, Unit/Feature suites pass, all three browser files pass, Vite exits zero, and diff-check produces no output.

- [x] **Step 5: Record exact non-secret evidence**

Update `application/docs/release-2-runbook.md` with:

- verification date and commit SHA;
- PHP/Laravel/MySQL versions;
- exact Unit/Feature/browser test and assertion counts;
- fixture version and tolerance `0.000001`;
- `0` fixture mismatches;
- migration status and non-sensitive table counts;
- browser viewport `1280x800` and optional Android `360x800` read-only check;
- pass/fail matrix for all six Rilis 2 acceptance criteria;
- reference to the approved backup/restore evidence without copying paths containing secrets;
- statement that no token, cookie, raw answer, IP, password, or personal identifier appears in the evidence.

Mark checkboxes in this plan only for commands that were actually run and whose output was read.

- [ ] **Step 6: Commit final evidence**

```bash
git add application/tests/Browser/AdminAnalysisFlowTest.php application/tests/Browser/ReleaseThreeFlowTest.php application/docs/release-2-runbook.md docs/superpowers/plans/2026-08-06-ueq-saw-release-2-gap-remediation.md
git commit -m "test: close release two remediation evidence"
```

## Release 2 Gap Traceability

| Audit gap / design requirement | Remediation evidence |
|---|---|
| Flag harus tersedia sebelum keputusan manual | Task 1 persists pending review flags in the submit transaction |
| Flag tidak mengeksklusi otomatis | Task 1 keeps decision/reviewer/time null until `ReviewSubmission` |
| Perubahan input membuat preview lama stale | Tasks 2-3 centralize revision/stale lifecycle for quality and technical changes |
| Perubahan kritis memiliki audit minimal | Tasks 2, 3, and 5 audit quality, technical input, and calculation creation |
| Tiga sampai lima informan anonim | Task 3 caps five and blocks SAW completeness below three |
| Setiap informan menilai 13 modul dan membagi 100 poin | Task 3 enforces exact unit keys, ranges, and total at domain boundary |
| Nilai individual, mean, SD, dan konsensus tersimpan | Task 3 stores individuals in tables and consensus/SD in immutable run snapshot |
| Alpha unit, warning n<20, warning <0.70 | Task 4 persists independent reliability warnings |
| Pooled alpha berlabel diagnostik | Task 4 creates six `ueq_pooled_results` rows per run |
| Statistik tersedia tidak boleh berubah menjadi nol/null yang menyesatkan | Task 4 separates descriptive and reliability availability |
| Setiap angka dapat ditelusuri dan hasil immutable | Task 5 guards result models and audits run creation |
| UEQ, gap, X, R, contribution, Vi, rank cocok fixture | Task 6 validates XLSX, JSON, pure services, and persisted run |
| Tabel angka lengkap tersedia | Task 7 renders CI, threshold, pooled, technical SD, X/R/contributions/Vi |
| Browser UAT dan MySQL evidence reproducible | Task 8 adds stable selectors, explicit browser suite, and runbook evidence |

## Plan Self-Review

- **Spec coverage:** Semua tujuh fungsi Rilis 2 pada bagian 5.2 dan enam acceptance criteria bagian 18.2 mempunyai task serta command verifikasi.
- **Scope:** Plan hanya menutup gap audit Rilis 2. Rumus fixture, Rilis 1, dan fitur Rilis 3 dipertahankan dengan regression tests.
- **Interfaces:** `CalculationInputChangeRecorder` dibuat sebelum dipakai Technical; `TechnicalConsensusData` dibuat sebelum dipakai snapshot/SAW; `UeqPooledResult` dibuat sebelum dipakai UI dan immutability tests.
- **Data integrity:** Quality flags dibuat di transaksi submit; technical data, revision, stale, dan audit berada dalam satu transaksi; result rows immutable.
- **Privacy:** Tidak ada bukti yang memuat token mentah/hash, cookie, raw answers, IP, password, atau identitas responden.
- **Placeholder scan:** Seluruh langkah mempunyai file, interface, test, implementasi konkret, command, dan expected result.
