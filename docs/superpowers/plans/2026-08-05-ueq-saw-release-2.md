# UEQ-SAW Release 2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Mengolah respons Rilis 1 menjadi hasil UEQ dan peringkat SAW yang dapat ditelusuri melalui review kualitas, input teknis per informan, dan calculation run preview yang immutable.

**Architecture:** Rilis 2 memiliki lima boundary: Quality untuk flag/review, UEQ untuk transformasi/statistik/gap, Technical untuk assessment dan bobot, SAW untuk normalisasi/ranking, serta Calculation untuk snapshot dan persistence hasil. Livewire hanya menangani input dan tampilan; rumus hanya berada di service domain.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4, Flux UI 2, MySQL 8, SQLite in-memory, Pest 5, dan fungsi matematika PHP native.

## Global Constraints

- Hanya periode Wong Reang, 13 modul, dan instrumen UEQ-ID-26-v1 dengan 26 item.
- Polaritas memakai ueq_items.positive_pole, bukan teks antarmuka; jawaban mentah tidak diubah.
- Keputusan review aktif hanya included atau excluded. Excluded membutuhkan alasan, reviewer, serta timestamp; flag tidak mengeksklusi otomatis.
- fast_completion berlaku untuk durasi kurang dari fast_response_seconds periode; identical_answers bila 26 skor mentah sama.
- Setiap run menyimpan versi algoritma, snapshot/hash input, benchmark, bobot, jumlah included/excluded, hasil antara, dan warnings. Jika input berubah run preview lama menjadi stale, tidak dihapus.
- Statistik tidak tersedia disimpan null dan unavailable_reason, bukan nol.
- SAW membutuhkan minimal dua alternatif lengkap. estimated_days <= 0 dan bobot selain total 100 ditolak.
- Sensitivitas, expert judgment, grafik/ekspor agregat, backlog, serta final/official lock tetap Rilis 3.
- Seluruh task memakai TDD dan diakhiri commit kecil. composer test wajib lulus sebelum completion claim.

---

## File Structure

| Path | Responsibility |
|---|---|
| application/database/migrations/2026_08_05_000010_create_quality_review_tables.php | review kualitas dan audit append-only |
| application/database/migrations/2026_08_05_000011_create_technical_assessment_tables.php | informan, C2/C3, bobot |
| application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php | run, hasil UEQ, hasil SAW |
| application/app/Domain/Quality | flag dan keputusan review |
| application/app/Domain/Ueq | transformasi, statistik, alpha, gap |
| application/app/Domain/Technical | assessment dan konsensus bobot |
| application/app/Domain/Saw | normalisasi, Vi, seri, rank |
| application/app/Application/Calculation | snapshot/hash/run writer |
| application/app/Livewire/Admin/Responses.php | review respons |
| application/app/Livewire/Admin/TechnicalAssessments.php | input informan |
| application/app/Livewire/Admin/Calculations.php | preview UEQ dan SAW |
| application/tests/Fixtures/ueq-saw-golden.json | data oracle dari spreadsheet independen |

## Task 1: Add auditable quality review

**Files:**
- Create: application/database/migrations/2026_08_05_000010_create_quality_review_tables.php
- Create: application/app/Models/QualityReview.php
- Create: application/app/Models/AuditEvent.php
- Create: application/app/Domain/Quality/QualityDecision.php
- Create: application/app/Domain/Quality/QualityFlagger.php
- Create: application/app/Application/Quality/ReviewSubmission.php
- Modify: application/app/Models/SurveySubmission.php
- Test: application/tests/Unit/Quality/QualityFlaggerTest.php
- Test: application/tests/Feature/Quality/ReviewSubmissionTest.php

**Interfaces:**
- Consumes: complete SurveySubmission, 26 answers, EvaluationPeriod threshold, User reviewer.
- Produces: QualityFlagger::for(SurveySubmission): array and ReviewSubmission::handle(SurveySubmission, User, QualityDecision, ?string): QualityReview.

- [ ] **Step 1: Write the failing tests**

~~~php
it('flags a fast identical response but does not automatically exclude it', function () {
    $submission = submissionWithAnswers(durationSeconds: 119, answers: array_fill(1, 26, 4));

    expect(app(QualityFlagger::class)->for($submission))
        ->toBe(['fast_completion' => true, 'identical_answers' => true]);
});

it('requires a reason and writes an audit event on exclusion', function () {
    app(ReviewSubmission::class)->handle($this->submission, $this->admin, QualityDecision::Excluded, '');

    $this->assertDatabaseHas('audit_events', ['action' => 'quality_review.updated']);
});
~~~

- [ ] **Step 2: Run the tests and verify failure**

Run: php artisan test tests/Unit/Quality/QualityFlaggerTest.php tests/Feature/Quality/ReviewSubmissionTest.php

Expected: FAIL because QualityFlagger and ReviewSubmission do not exist.

- [ ] **Step 3: Implement minimal schema and services**

~~~php
// quality_reviews
$table->foreignId('survey_submission_id')->unique()->constrained()->cascadeOnDelete();
$table->json('flags');
$table->string('decision');
$table->text('reason')->nullable();
$table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
$table->timestamp('reviewed_at');

// audit_events
$table->string('action');
$table->string('auditable_type');
$table->unsignedBigInteger('auditable_id');
$table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
$table->json('old_values')->nullable();
$table->json('new_values');
~~~

Create enum cases Included and Excluded. QualityFlagger always returns both flag keys. ReviewSubmission runs in one database transaction: reject empty excluded reason, upsert active review, write old/new decision, reason, flags, reviewer, and time into AuditEvent. Add SurveySubmission::qualityReview(): HasOne.

- [ ] **Step 4: Run focused verification**

Run: php artisan test tests/Unit/Quality/QualityFlaggerTest.php tests/Feature/Quality/ReviewSubmissionTest.php; vendor/bin/phpstan analyse --memory-limit=512M

Expected: PASS and PHPStan reports zero errors.

- [ ] **Step 5: Commit**

~~~powershell
git add application/database/migrations/2026_08_05_000010_create_quality_review_tables.php application/app/Models application/app/Domain/Quality application/app/Application/Quality application/tests/Unit/Quality application/tests/Feature/Quality
git commit -m "feat: add auditable survey quality review"
~~~

## Task 2: Deliver protected response review UI

**Files:**
- Create: application/app/Application/Quality/ResponseReviewRow.php
- Create: application/app/Application/Quality/ResponseReviewQuery.php
- Create: application/app/Livewire/Admin/Responses.php
- Create: application/resources/views/livewire/admin/responses.blade.php
- Modify: application/routes/web.php
- Modify: application/resources/views/livewire/admin/dashboard.blade.php
- Test: application/tests/Feature/Admin/ResponsesTest.php

**Interfaces:**
- Consumes: ResponseReviewQuery::for(EvaluationPeriod) and ReviewSubmission.
- Produces: admin.responses with unit, duration, flags, decision, reason, reviewer, and time only.

- [ ] **Step 1: Write the failing feature test**

~~~php
it('protects response review and saves an exclusion', function () {
    $this->get(route('admin.responses'))->assertRedirect(route('login'));

    Livewire::actingAs($this->admin, Responses::class)
        ->call('openReview', $this->submission->id)
        ->set('decision', 'excluded')
        ->set('reason', 'Durasi 8 detik dan seluruh jawaban identik.')
        ->call('saveReview')
        ->assertHasNoErrors();
});
~~~

- [ ] **Step 2: Run the test and verify failure**

Run: php artisan test tests/Feature/Admin/ResponsesTest.php

Expected: FAIL because the route and component do not exist.

- [ ] **Step 3: Implement route, component, and query**

~~~php
Route::get('/responses', Responses::class)->name('responses');

public function saveReview(ReviewSubmission $reviews): void
{
    $reviews->handle(
        SurveySubmission::query()->findOrFail($this->submissionId),
        auth()->user(),
        QualityDecision::from($this->decision),
        $this->reason,
    );

    $this->reset('submissionId', 'reason');
}
~~~

Use the existing auth, verified, admin.2fa route group. Translate flags to Indonesian labels, provide empty state, and add dashboard link Respons. Do not query/render token hash, cookies, IP, user agent, or anonymous respondent ID.

- [ ] **Step 4: Run focused test**

Run: php artisan test tests/Feature/Admin/ResponsesTest.php

Expected: PASS for route protection, saved review, and absent sensitive fields.

- [ ] **Step 5: Commit**

~~~powershell
git add application/app/Application/Quality application/app/Livewire/Admin/Responses.php application/resources/views/livewire/admin/responses.blade.php application/routes/web.php application/resources/views/livewire/admin/dashboard.blade.php application/tests/Feature/Admin/ResponsesTest.php
git commit -m "feat: add response quality review screen"
~~~

## Task 3: Create golden fixture and pure UEQ engine

**Files:**
- Create: docs/research/ueq-saw-golden-fixture.xlsx
- Create: application/tests/Fixtures/ueq-saw-golden.json
- Create: application/app/Domain/Ueq/UeqTransformer.php
- Create: application/app/Domain/Ueq/UeqScaleStatistics.php
- Create: application/app/Domain/Ueq/UeqStatisticsCalculator.php
- Test: application/tests/Unit/Ueq/UeqTransformerTest.php
- Test: application/tests/Unit/Ueq/UeqStatisticsCalculatorTest.php

**Interfaces:**
- Consumes: ordered item mapping and included raw answers.
- Produces: UeqTransformer::score(int, string): int and UeqStatisticsCalculator::forScale(...): UeqScaleStatistics.

- [ ] **Step 1: Create fixture and failing unit tests**

~~~json
{
  "algorithm_version": "ueq-saw-v1",
  "tolerance": 0.000001,
  "expected": {"transform_right_7": 3, "transform_left_1": 3}
}
~~~

Complete the spreadsheet independently before code. It must contain all 26 actual mappings, two or more units, included/excluded submissions, six Good thresholds, three technical informants, X/R values, weights, contributions, Vi, rank, and a tie. Copy machine-readable output into JSON.

~~~php
expect(app(UeqTransformer::class)->score(7, 'right'))->toBe(3);
expect(app(UeqTransformer::class)->score(1, 'left'))->toBe(3);
~~~

- [ ] **Step 2: Run tests and verify failure**

Run: php artisan test tests/Unit/Ueq/UeqTransformerTest.php tests/Unit/Ueq/UeqStatisticsCalculatorTest.php

Expected: FAIL because UEQ services do not exist.

- [ ] **Step 3: Implement deterministic transformation and statistics**

~~~php
public function score(int $rawScore, string $positivePole): int
{
    return match ($positivePole) {
        'right' => $rawScore - 4,
        'left' => 4 - $rawScore,
        default => throw new InvalidArgumentException('Unknown positive pole.'),
    };
}
~~~

Calculate n, mean, sample SD with n - 1, SE, two-sided 95 percent t interval, and Cronbach alpha from item and total-score variance. Use an explicit critical-t table for df 1 to 30 and 1.959963984540054 above df 30. Return n_below_2 or zero_variance where needed and never divide by zero.

- [ ] **Step 4: Match all expected fixture values**

~~~php
expect($result->mean)->toEqualWithDelta($fixture['expected']['ueq']['ibadah-yu']['Attractiveness']['mean'], $fixture['tolerance']);
expect($result->cronbachAlpha)->toEqualWithDelta($fixture['expected']['ueq']['ibadah-yu']['Attractiveness']['alpha'], $fixture['tolerance']);
~~~

Run: php artisan test tests/Unit/Ueq/UeqTransformerTest.php tests/Unit/Ueq/UeqStatisticsCalculatorTest.php

Expected: PASS for all 26 mappings, scores 1/4/7, unavailable states, and golden values.

- [ ] **Step 5: Commit**

~~~powershell
git add docs/research/ueq-saw-golden-fixture.xlsx application/tests/Fixtures/ueq-saw-golden.json application/app/Domain/Ueq application/tests/Unit/Ueq
git commit -m "feat: add verified UEQ statistics engine"
~~~

## Task 4: Persist calculation preview and UEQ/gap results

**Files:**
- Create: application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php
- Create: application/app/Models/CalculationRun.php
- Create: application/app/Models/UeqResult.php
- Create: application/app/Application/Calculation/CalculationInputSnapshot.php
- Create: application/app/Application/Calculation/CalculationRunService.php
- Create: application/app/Application/Calculation/UeqResultWriter.php
- Test: application/tests/Feature/Calculation/CalculationRunServiceTest.php

**Interfaces:**
- Consumes: quality decisions, UEQ services, verified benchmarks, items, actor.
- Produces: CalculationRunService::preview(EvaluationPeriod, User): CalculationRun.

- [ ] **Step 1: Write the failing preview tests**

~~~php
it('writes a preview with non-negative gaps and reproducible input hash', function () {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);

    expect($run->status)->toBe('preview')
        ->and($run->input_hash)->toHaveLength(64)
        ->and($run->ueqResults->every(fn (UeqResult $row) => (float) $row->gap >= 0))->toBeTrue();
});

it('marks older preview stale after quality change', function () {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);
    app(ReviewSubmission::class)->handle($this->submission, $this->admin, QualityDecision::Excluded, 'Pola tidak layak.');

    expect($run->fresh()->status)->toBe('stale');
});
~~~

- [ ] **Step 2: Run it to verify failure**

Run: php artisan test tests/Feature/Calculation/CalculationRunServiceTest.php

Expected: FAIL because tables and service do not exist.

- [ ] **Step 3: Implement schema and snapshot lifecycle**

~~~php
$table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
$table->string('algorithm_version');
$table->string('status');
$table->string('input_hash', 64);
$table->json('input_snapshot');
$table->json('warnings');
$table->unsignedInteger('included_count');
$table->unsignedInteger('excluded_count');
$table->foreignId('created_by')->constrained('users')->restrictOnDelete();
$table->timestamp('calculated_at');
~~~

For ueq_results create run/unit/scale unique fields plus n, mean, standard_deviation, standard_error, ci95_lower, ci95_upper, cronbach_alpha, gap, unavailable_reason. All numeric result fields use decimal(18,10) and are nullable except n. Sort every ID/key recursively before SHA-256 JSON hashing. Snapshot configuration, item mapping, benchmark source/version/threshold, decisions, included/excluded submission IDs, and all included raw answers. Reject missing source or null verified_at by naming its scale. A different hash makes previous preview stale.

- [ ] **Step 4: Run focused regression**

Run: php artisan test tests/Feature/Calculation/CalculationRunServiceTest.php tests/Feature/Study/PeriodActivationTest.php

Expected: PASS for snapshot, non-negative gap, stale lifecycle, and Rilis 1 regression.

- [ ] **Step 5: Commit**

~~~powershell
git add application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php application/app/Models/CalculationRun.php application/app/Models/UeqResult.php application/app/Application/Calculation application/tests/Feature/Calculation
git commit -m "feat: persist traceable UEQ calculation previews"
~~~

## Task 5: Add technical informants, assessment, and consensus

**Files:**
- Create: application/database/migrations/2026_08_05_000011_create_technical_assessment_tables.php
- Create: application/app/Models/TechnicalInformant.php
- Create: application/app/Models/TechnicalAssessment.php
- Create: application/app/Models/CriteriaWeight.php
- Create: application/app/Domain/Technical/SaveTechnicalAssessment.php
- Create: application/app/Domain/Technical/TechnicalConsensus.php
- Create: application/app/Livewire/Admin/TechnicalAssessments.php
- Create: application/resources/views/livewire/admin/technical-assessments.blade.php
- Modify: application/routes/web.php
- Test: application/tests/Unit/Technical/TechnicalConsensusTest.php
- Test: application/tests/Feature/Admin/TechnicalAssessmentsTest.php

**Interfaces:**
- Consumes: period, unit, informant code, days, urgency, C1-C3 points.
- Produces: TechnicalConsensus::for(EvaluationPeriod) with mean days, mean urgency, and normalized c1/c2/c3.

- [ ] **Step 1: Write the failing validation tests**

~~~php
Livewire::actingAs($this->admin, TechnicalAssessments::class)
    ->set('weights', ['c1' => 50, 'c2' => 20, 'c3' => 20])
    ->call('saveWeights')
    ->assertHasErrors(['weights' => 'total']);

expect(app(TechnicalConsensus::class)->for($this->period)->weights)
    ->toHaveKeys(['c1', 'c2', 'c3']);
~~~

- [ ] **Step 2: Run it to verify failure**

Run: php artisan test tests/Unit/Technical/TechnicalConsensusTest.php tests/Feature/Admin/TechnicalAssessmentsTest.php

Expected: FAIL because models and component do not exist.

- [ ] **Step 3: Implement scope and validation**

~~~php
// technical_informants
$table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
$table->string('anonymous_code');
$table->unique(['evaluation_period_id', 'anonymous_code']);

// technical_assessments
$table->foreignId('technical_informant_id')->constrained()->cascadeOnDelete();
$table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
$table->decimal('estimated_days', 12, 2);
$table->unsignedTinyInteger('architecture_urgency');
$table->unique(['technical_informant_id', 'evaluation_unit_id']);

// criteria_weights
$table->foreignId('technical_informant_id')->unique()->constrained()->cascadeOnDelete();
$table->unsignedTinyInteger('c1_points');
$table->unsignedTinyInteger('c2_points');
$table->unsignedTinyInteger('c3_points');
~~~

Validate numeric estimated_days greater than zero, urgency integer 1 through 5, each point integer 0 through 100, and exact total 100. Form saves one informant at a time, renders all 13 fixed units, retains missing values as missing, and adds protected admin.technical-assessments plus dashboard link Informan.

- [ ] **Step 4: Run focused verification**

Run: php artisan test tests/Unit/Technical/TechnicalConsensusTest.php tests/Feature/Admin/TechnicalAssessmentsTest.php

Expected: PASS for uniqueness, days/urgency range, total error, and normalized consensus.

- [ ] **Step 5: Commit**

~~~powershell
git add application/database/migrations/2026_08_05_000011_create_technical_assessment_tables.php application/app/Models application/app/Domain/Technical application/app/Livewire/Admin/TechnicalAssessments.php application/resources/views/livewire/admin/technical-assessments.blade.php application/routes/web.php application/tests/Unit/Technical application/tests/Feature/Admin/TechnicalAssessmentsTest.php
git commit -m "feat: collect technical assessments and weights"
~~~

## Task 6: Calculate and persist SAW ranking

**Files:**
- Create: application/app/Domain/Saw/SawAlternative.php
- Create: application/app/Domain/Saw/SawResultData.php
- Create: application/app/Domain/Saw/SawCalculator.php
- Create: application/app/Application/Calculation/SawResultWriter.php
- Modify: application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php
- Modify: application/app/Application/Calculation/CalculationRunService.php
- Test: application/tests/Unit/Saw/SawCalculatorTest.php
- Test: application/tests/Feature/Calculation/CalculationRunServiceTest.php

**Interfaces:**
- Consumes: C1 gap, C2 mean days, C3 mean urgency, consensus weights.
- Produces: SawCalculator::rank(array, array): array and one SawResult per complete alternative.

- [ ] **Step 1: Write failing SAW tests**

~~~php
it('normalizes all-zero gaps to zero', function () {
    $rows = app(SawCalculator::class)->rank($this->allZeroGap, ['c1' => .4, 'c2' => .3, 'c3' => .3]);

    expect(collect($rows)->every(fn (SawResultData $row) => $row->r1 === 0.0))->toBeTrue();
});

it('rejects zero days and retains tied rank', function () {
    expect(fn () => app(SawCalculator::class)->rank($this->zeroDays, $this->weights))
        ->toThrow(DomainException::class, 'estimated_days');
});
~~~

- [ ] **Step 2: Run it to verify failure**

Run: php artisan test tests/Unit/Saw/SawCalculatorTest.php

Expected: FAIL because SawCalculator does not exist.

- [ ] **Step 3: Implement formula, ties, and persistence**

~~~php
$r1 = $maxGap === 0.0 ? 0.0 : $alternative->gap / $maxGap;
$r2 = $minDays / $alternative->meanDays;
$r3 = $alternative->meanUrgency / $maxUrgency;
$vi = ($weights['c1'] * $r1) + ($weights['c2'] * $r2) + ($weights['c3'] * $r3);
~~~

Add saw_results columns: run ID, unit ID, x1_gap, x2_days, x3_urgency, r1-r3, contribution_c1-c3, preference_value, rank, is_tied, and unique run/unit. Reject fewer than two complete alternatives. Sort full Vi descending, then unit code. Equal Vi within fixture tolerance shares rank. Append warning if all C1 are zero.

- [ ] **Step 4: Match golden fixture**

Run: php artisan test tests/Unit/Saw/SawCalculatorTest.php tests/Feature/Calculation/CalculationRunServiceTest.php

Expected: PASS for normalisation, zero gaps, missing alternatives, zero days, weights, Vi, ties, ranks, and fixture values.

- [ ] **Step 5: Commit**

~~~powershell
git add application/app/Domain/Saw application/app/Application/Calculation/SawResultWriter.php application/app/Application/Calculation/CalculationRunService.php application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php application/tests/Unit/Saw application/tests/Feature/Calculation/CalculationRunServiceTest.php
git commit -m "feat: calculate traceable SAW preview rankings"
~~~

## Task 7: Build admin calculation preview and traceability UI

**Files:**
- Create: application/app/Application/Calculation/CalculationRunView.php
- Create: application/app/Application/Calculation/CalculationRunQuery.php
- Create: application/app/Livewire/Admin/Calculations.php
- Create: application/resources/views/livewire/admin/calculations.blade.php
- Modify: application/routes/web.php
- Modify: application/resources/views/livewire/admin/dashboard.blade.php
- Test: application/tests/Feature/Admin/CalculationsTest.php

**Interfaces:**
- Consumes: CalculationRunService::preview and persisted UEQ/SAW results.
- Produces: admin.calculations and runPreview() action.

- [ ] **Step 1: Write failing UI test**

~~~php
Livewire::actingAs($this->admin, Calculations::class)
    ->call('runPreview')
    ->assertHasNoErrors()
    ->assertSee('Preview berhasil dibuat')
    ->assertSee('Input hash');

$this->get(route('admin.calculations'))
    ->assertDontSee('Tetapkan hasil resmi')
    ->assertDontSee('Sensitivitas');
~~~

- [ ] **Step 2: Run it to verify failure**

Run: php artisan test tests/Feature/Admin/CalculationsTest.php

Expected: FAIL because route and component do not exist.

- [ ] **Step 3: Implement query and page**

~~~php
Route::get('/calculations', Calculations::class)->name('calculations');

public function runPreview(CalculationRunService $service): void
{
    $this->runId = $service->preview($this->period(), auth()->user())->id;
    $this->dispatch('calculation-preview-created');
}
~~~

Show period, algorithm version, status, timestamp, actor, input hash, included/excluded count, warnings. UEQ table: unit, scale, n, mean, SD, SE, CI, alpha, Good threshold, gap, unavailable reason. SAW table: X1-X3, R1-R3, contributions, Vi, rank, tie. Add dashboard link Kalkulasi. Do not expose Rilis 3 controls or wording.

- [ ] **Step 4: Run focused test**

Run: php artisan test tests/Feature/Admin/CalculationsTest.php

Expected: PASS for protected preview, metadata, warnings, and no final controls.

- [ ] **Step 5: Commit**

~~~powershell
git add application/app/Application/Calculation/CalculationRunView.php application/app/Application/Calculation/CalculationRunQuery.php application/app/Livewire/Admin/Calculations.php application/resources/views/livewire/admin/calculations.blade.php application/routes/web.php application/resources/views/livewire/admin/dashboard.blade.php application/tests/Feature/Admin/CalculationsTest.php
git commit -m "feat: show UEQ and SAW calculation previews"
~~~

## Task 8: Verify fixture, MySQL, privacy, and browser UAT

**Files:**
- Create: application/tests/Browser/AdminAnalysisFlowTest.php
- Create: application/docs/release-2-runbook.md
- Modify: docs/superpowers/plans/2026-08-05-ueq-saw-release-2.md

**Interfaces:**
- Consumes: completed Tasks 1-7 and local MySQL study data.
- Produces: non-secret evidence for each Rilis 2 acceptance criterion.

- [ ] **Step 1: Write failing browser UAT**

~~~php
it('lets an administrator review, enter technical data, and see a preview', function () {
    $page = visit('/login');

    $page->assertSee('Dashboard progres')
        ->click('Respons')->assertSee('Review kualitas respons')
        ->click('Informan')->assertSee('Penilaian teknis')
        ->click('Kalkulasi')->click('Jalankan preview')
        ->assertSee('Input hash');
});
~~~

Use test-created verified 2FA session; never place login credentials in test source.

- [ ] **Step 2: Run browser and full test suite**

Run: php artisan test tests/Browser/AdminAnalysisFlowTest.php; composer test

Expected: PASS. Record real assertion counts; warnings are reported separately.

- [ ] **Step 3: Check MySQL runtime shape**

~~~powershell
php artisan migrate --force
php artisan db:seed --class=WongReangStudySeeder --force
php artisan test tests/Feature/Calculation/CalculationRunServiceTest.php
~~~

Confirm migration status, one study period, 13 units, 26 current-version items, six verified benchmarks, and no token hash in review/calculation UI.

- [ ] **Step 4: Record reproducible evidence**

Write application/docs/release-2-runbook.md with date, commit SHA, commands, output summary, migration status, fixture tolerance, new-table row counts, browser viewport, and Rilis 2 pass/fail matrix. Reference existing backup/restore procedure. Omit passwords, token values, cookies, raw answers, and personal identifiers.

- [ ] **Step 5: Run final gates and commit**

Run: composer test; npm run build; git diff --check

Expected: all exit 0.

~~~powershell
git add application/tests/Browser/AdminAnalysisFlowTest.php application/docs/release-2-runbook.md docs/superpowers/plans/2026-08-05-ueq-saw-release-2.md
git commit -m "test: verify release two analysis workflow"
~~~

## Release 2 Acceptance Traceability

| Acceptance criterion | Plan evidence |
|---|---|
| Semua polaritas tervalidasi | Task 3 menguji 26 item dan kedua kutub. |
| Statistik UEQ dan gap cocok fixture | Tasks 3-4 membandingkan fixture dan menyimpan ueq_results. |
| Assessment setiap informan terpisah | Task 5 uniqueness dan persistence. |
| Bobot selain total 100 ditolak | Task 5 domain dan Livewire validation. |
| X, R, Vi, rank cocok fixture | Task 6 fixture test dan saw_results. |
| Setiap angka tertelusur | Tasks 4 dan 7 menyimpan snapshot/hash, actor, time, FK. |

## Plan Self-Review

- Semua tujuh butir scope dan enam acceptance criteria Rilis 2 pada desain dipetakan ke Tasks 1-8.
- Sensitivitas, expert judgment, laporan, dan final lock sengaja tertunda ke Rilis 3.
- Perubahan review membuat audit event dan run stale; snapshot mencakup seluruh input angka.
- Semua layar memakai middleware admin yang ada dan tidak memperlihatkan token atau identitas responden.
