# Release One Specification Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup seluruh gap audit Rilis 1 sehingga survei hanya dapat menerima submission pada periode yang sah, konfigurasi penelitian dapat dibuktikan terkunci, consent dan eligibility memenuhi spesifikasi, ekspor mentah dapat ditelusuri, dan release gate mempunyai bukti aktual.

**Architecture:** Pertahankan modular monolith Laravel yang ada. Tambahkan gate domain terpusat untuk penerimaan submission, action tunggal untuk eligibility immutable, bukti readiness operasional yang tersimpan di database, dan fingerprint SHA-256 atas konfigurasi penelitian. Livewire tetap menjadi adapter UI; aturan integritas berada pada service/action domain dan selalu diperiksa ulang pada boundary transaksi.

**Tech Stack:** Laravel 13, PHP 8.3+, Livewire 4, Tailwind CSS 4, MySQL 8, Pest 5, Pest Browser, PhpSpreadsheet, Vite, dan Playwright.

## Global Constraints

- Semua source aplikasi berada di `application/`; semua perintah PHP, Composer, NPM, dan Artisan dijalankan dari folder tersebut.
- Gunakan Laravel 13, Livewire 4, Tailwind CSS 4, MySQL 8, dan Pest 5; jangan menurunkan versi dependency.
- Hanya satu studi Wong Reang Apps, satu akun Peneliti/Admin, dan satu periode aktif; jangan menambah tenant, organisasi, role matrix, atau registrasi publik.
- Responden tidak mempunyai akun dan tidak menyimpan NIK, nama, nomor telepon, alamat lengkap, token mentah, atau IP dalam dataset penelitian.
- Satu submission mewakili satu responden anonim, satu periode, satu modul, dan tepat 26 jawaban bernilai 1 sampai 7.
- Token mentah hanya berada dalam cookie terenkripsi; database hanya menyimpan HMAC-SHA256 menggunakan `SURVEY_TOKEN_KEY`.
- Form UEQ tetap empat langkah 7-7-6-6 dan tidak mengubah urutan instrumen.
- Eligibility bersifat immutable setelah pertama kali dicatat untuk kombinasi periode dan token.
- Periode hanya menerima submission jika status `active`, waktu sekarang berada di dalam jendela buka/tutup, dan fingerprint konfigurasi masih cocok.
- Aktivasi resmi harus mempunyai admin terverifikasi dengan 2FA serta bukti HTTPS, backup/restore, dan uji submit.
- Jangan mengubah rumus UEQ, SAW, sensitivitas, hasil calculation run, atau perilaku Rilis 2/3 selain koreksi tipe yang diperlukan untuk mengembalikan release gate.
- Tulis test gagal terlebih dahulu, lihat kegagalannya, implementasikan perubahan minimal, lalu jalankan test terfokus dan gate lengkap.
- Jangan mencatat credential, cookie, token, jawaban responden, atau secret environment dalam log, test fixture, dokumentasi, atau commit.

---

## File Map

### Gate survei dan eligibility

- Create: `application/app/Domain/Study/SurveyPeriodGate.php` — satu sumber kebenaran status, jendela waktu, lock, dan fingerprint periode.
- Create: `application/app/Application/Survey/RecordRespondentEligibility.php` — menyimpan profil eligibility tepat sekali secara transaksional.
- Modify: `application/app/Http/Controllers/SurveyEntryController.php` — memakai gate sebelum menerbitkan token.
- Modify: `application/app/Domain/Survey/SurveyContext.php` — memakai gate pada seluruh halaman responden.
- Modify: `application/app/Application/Survey/SubmitSurvey.php` — memeriksa gate dan ownership kembali di boundary transaksi.
- Modify: `application/app/Livewire/Survey/ConsentScreener.php` — delegasi penyimpanan eligibility ke action immutable.

### Consent, quality rules, readiness, dan configuration lock

- Create: `application/database/migrations/2026_08_06_000014_add_release_one_readiness_fields_to_evaluation_periods.php`.
- Create: `application/database/migrations/2026_08_06_000015_create_period_readiness_evidence_table.php`.
- Create: `application/database/migrations/2026_08_06_000016_add_configuration_hash_to_evaluation_periods.php`.
- Create: `application/app/Domain/Study/ReadinessEvidenceKind.php`.
- Create: `application/app/Models/PeriodReadinessEvidence.php`.
- Create: `application/app/Application/Study/RecordReadinessEvidence.php`.
- Create: `application/app/Domain/Study/StudyConfigurationHasher.php`.
- Modify: `application/app/Domain/Study/PeriodReadinessService.php`.
- Modify: `application/app/Domain/Quality/QualityFlagger.php`.
- Modify: `application/app/Models/EvaluationPeriod.php`.
- Modify: `application/app/Livewire/Admin/StudySettings.php`.
- Modify: `application/resources/views/livewire/admin/study-settings.blade.php`.
- Modify: `application/resources/views/livewire/survey/consent-screener.blade.php`.
- Modify: `application/database/seeders/WongReangStudySeeder.php`.

### Export, verification, dan dokumentasi

- Modify: `application/app/Application/Reporting/RawSurveyExport.php`.
- Modify: `application/tests/Feature/Admin/RawSurveyExportTest.php`.
- Create: `application/tests/Feature/Study/SurveyPeriodGateTest.php`.
- Create: `application/tests/Feature/Study/StudyConfigurationHasherTest.php`.
- Create: `application/tests/Feature/Study/ReadinessEvidenceTest.php`.
- Modify: `application/tests/Pest.php` — shared ready-admin/evidence and accepting-period fixtures.
- Modify: `application/tests/Feature/Study/PeriodActivationTest.php`.
- Modify: `application/tests/Feature/Survey/ConsentScreenerTest.php`.
- Modify: `application/tests/Feature/Survey/SubmitSurveyTest.php`.
- Modify: `application/tests/Feature/Survey/UnitChooserTest.php`.
- Modify: `application/tests/Browser/OfflineDraftTest.php`.
- Modify: `application/docs/release-1-runbook.md`.

---

### Task 1: Enforce the survey acceptance window at every boundary

**Files:**
- Create: `application/app/Domain/Study/SurveyPeriodGate.php`
- Create: `application/tests/Feature/Study/SurveyPeriodGateTest.php`
- Modify: `application/app/Http/Controllers/SurveyEntryController.php`
- Modify: `application/app/Domain/Survey/SurveyContext.php`
- Modify: `application/app/Application/Survey/SubmitSurvey.php`
- Modify: `application/tests/Feature/Survey/SubmitSurveyTest.php`

**Interfaces:**
- Consumes: `EvaluationPeriod`, current application clock, and later `StudyConfigurationHasher` from Task 5.
- Produces: `SurveyPeriodGate::issues(EvaluationPeriod $period): list<string>` and `SurveyPeriodGate::assertAccepting(EvaluationPeriod $period): void`.

- [ ] **Step 1: Write failing gate tests**

Create tests for a draft period, future active period, expired active period, active period without `configuration_locked_at`, and a valid active period:

```php
it('accepts only a locked active period inside its configured window', function () {
    $gate = app(\App\Domain\Study\SurveyPeriodGate::class);
    $period = \App\Models\EvaluationPeriod::factory()->create([
        'status' => \App\Domain\Study\PeriodStatus::Active,
        'opens_at' => now()->subMinute(),
        'closes_at' => now()->addMinute(),
        'configuration_locked_at' => now(),
    ]);

    expect($gate->issues($period))->toBe([]);
});
```

Use datasets for the four rejected states and assert the exact Indonesian recovery message for each state.

- [ ] **Step 2: Run the gate tests and confirm the missing class failure**

Run: `php artisan test tests/Feature/Study/SurveyPeriodGateTest.php`

Expected: FAIL because `SurveyPeriodGate` does not exist.

- [ ] **Step 3: Implement the centralized gate**

Implement `issues()` and `assertAccepting()`:

```php
final class SurveyPeriodGate
{
    /** @return list<string> */
    public function issues(EvaluationPeriod $period): array
    {
        $issues = [];

        if ($period->status !== PeriodStatus::Active) {
            $issues[] = 'Periode penelitian tidak aktif.';
        }
        if ($period->opens_at === null || now()->lt($period->opens_at)) {
            $issues[] = 'Periode penelitian belum dibuka.';
        }
        if ($period->closes_at === null || now()->gt($period->closes_at)) {
            $issues[] = 'Periode penelitian sudah ditutup.';
        }
        if ($period->configuration_locked_at === null) {
            $issues[] = 'Konfigurasi periode belum dikunci.';
        }

        return array_values(array_unique($issues));
    }

    public function assertAccepting(EvaluationPeriod $period): void
    {
        $issues = $this->issues($period);
        throw_if($issues !== [], DomainException::class, implode(' ', $issues));
    }
}
```

Task 5 extends this class with fingerprint validation; do not duplicate that check elsewhere.

- [ ] **Step 4: Apply the gate to HTTP entry, context, and transaction boundary**

Inject the gate into `SurveyEntryController` and `SurveyContext`; translate `DomainException` into a 404 response without exposing internal state. In `SubmitSurvey::handle()`, fetch the period by `periodId`, call `assertAccepting()`, and verify before inserting that:

```php
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
```

Keep the existing session ownership check inside the same transaction.

- [ ] **Step 5: Add stale-component and direct-action regression tests**

Mount the wizard while active, move `closes_at` to one minute ago, submit, and assert no submission or answers exist. Call `SubmitSurvey` directly with an ineligible respondent and with a mismatched instrument version; assert both fail without partial rows.

- [ ] **Step 6: Run focused tests**

Run: `php artisan test tests/Feature/Study/SurveyPeriodGateTest.php tests/Feature/Survey/AnonymousTokenTest.php tests/Feature/Survey/ConsentScreenerTest.php tests/Feature/Survey/UeqWizardTest.php tests/Feature/Survey/SubmitSurveyTest.php`

Expected: all selected tests pass.

- [ ] **Step 7: Commit the period gate**

```bash
git add application/app/Domain/Study/SurveyPeriodGate.php application/app/Http/Controllers/SurveyEntryController.php application/app/Domain/Survey/SurveyContext.php application/app/Application/Survey/SubmitSurvey.php application/tests/Feature/Study/SurveyPeriodGateTest.php application/tests/Feature/Survey/SubmitSurveyTest.php
git commit -m "fix: enforce release one survey period gate"
```

---

### Task 2: Make screener eligibility immutable per period and token

**Files:**
- Create: `application/app/Application/Survey/RecordRespondentEligibility.php`
- Modify: `application/app/Livewire/Survey/ConsentScreener.php`
- Modify: `application/tests/Feature/Survey/ConsentScreenerTest.php`

**Interfaces:**
- Consumes: validated consent/screener values, `EvaluationPeriod`, and `AnonymousRespondent`.
- Produces: `RecordRespondentEligibility::handle(EvaluationPeriod $period, AnonymousRespondent $respondent, int $age, bool $isIndramayuResident, bool $hasUsedWongReang): RespondentProfile`.

- [ ] **Step 1: Replace the mutable-profile test with immutable expectations**

Change the existing test that expects age 21 after resubmission. First submit an ineligible profile, then submit eligible-looking values for the same token. Assert the original age, booleans, `eligible`, `consented_at`, and `screened_at` do not change.

- [ ] **Step 2: Run the test and prove current behavior is wrong**

Run: `php artisan test tests/Feature/Survey/ConsentScreenerTest.php --filter="stores eligibility only once"`

Expected: FAIL because `updateOrCreate()` overwrites the profile.

- [ ] **Step 3: Implement the serialized record-once action**

Lock the respondent row so two concurrent first submissions cannot race around the unique profile constraint:

```php
public function handle(
    EvaluationPeriod $period,
    AnonymousRespondent $respondent,
    int $age,
    bool $isIndramayuResident,
    bool $hasUsedWongReang,
): RespondentProfile {
    return DB::transaction(function () use ($period, $respondent, $age, $isIndramayuResident, $hasUsedWongReang) {
        AnonymousRespondent::query()->lockForUpdate()->findOrFail($respondent->id);

        $existing = RespondentProfile::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return RespondentProfile::query()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $respondent->id,
            'consented_at' => now(),
            'age' => $age,
            'is_indramayu_resident' => $isIndramayuResident,
            'has_used_wong_reang' => $hasUsedWongReang,
            'eligible' => $age >= $period->minimum_age && $isIndramayuResident && $hasUsedWongReang,
            'screened_at' => now(),
        ]);
    }, attempts: 3);
}
```

- [ ] **Step 4: Delegate the Livewire component to the action**

After validation, call the action and redirect solely from the returned profile's stored `eligible` value. Remove `RespondentProfile::updateOrCreate()` from the component.

- [ ] **Step 5: Add eligible-profile immutability and concurrency coverage**

Add a second test proving an eligible profile cannot be changed to ineligible. Add an action test that calls `handle()` twice and asserts one database row and unchanged timestamps.

- [ ] **Step 6: Run screener and chooser tests**

Run: `php artisan test tests/Feature/Survey/ConsentScreenerTest.php tests/Feature/Survey/UnitChooserTest.php`

Expected: all tests pass.

- [ ] **Step 7: Commit immutable eligibility**

```bash
git add application/app/Application/Survey/RecordRespondentEligibility.php application/app/Livewire/Survey/ConsentScreener.php application/tests/Feature/Survey/ConsentScreenerTest.php
git commit -m "fix: record respondent eligibility only once"
```

---

### Task 3: Make consent and quality-rule readiness explicit

**Files:**
- Create: `application/database/migrations/2026_08_06_000014_add_release_one_readiness_fields_to_evaluation_periods.php`
- Modify: `application/app/Models/EvaluationPeriod.php`
- Modify: `application/database/seeders/WongReangStudySeeder.php`
- Modify: `application/app/Livewire/Admin/StudySettings.php`
- Modify: `application/resources/views/livewire/admin/study-settings.blade.php`
- Modify: `application/resources/views/livewire/survey/consent-screener.blade.php`
- Modify: `application/app/Domain/Study/PeriodReadinessService.php`
- Modify: `application/app/Domain/Quality/QualityFlagger.php`
- Modify: `application/tests/Feature/Study/PeriodActivationTest.php`
- Modify: `application/tests/Pest.php`
- Modify: `application/tests/Feature/Quality/QualityFlaggerTest.php`

**Interfaces:**
- Consumes: the existing `consent_text` as purpose/overview and `fast_response_seconds` as the duration threshold.
- Produces: structured consent fields plus `quality_rules_version` and `identical_answers_flag_enabled` locked with the period.

- [ ] **Step 1: Write failing readiness and rendering tests**

Assert activation reports one actionable issue for every missing field:

```php
[
    'consent_data_description',
    'consent_cookie_description',
    'consent_estimated_minutes',
    'consent_withdrawal_description',
    'research_contact',
    'quality_rules_version',
]
```

Also assert `fast_response_seconds = 0` is rejected and the public consent page renders purpose, stored data, cookie use, estimated minutes, right to stop, and researcher contact.

- [ ] **Step 2: Run focused tests and confirm missing-column failures**

Run: `php artisan test tests/Feature/Study/PeriodActivationTest.php tests/Feature/Survey/ConsentScreenerTest.php tests/Feature/Quality/QualityFlaggerTest.php`

Expected: FAIL because the structured fields do not exist.

- [ ] **Step 3: Add schema and casts**

Add these columns after `consent_text`:

```php
$table->text('consent_data_description')->default('Jawaban UEQ mentah, waktu pengisian, urutan modul, dan profil kelayakan tanpa nama disimpan untuk analisis penelitian.');
$table->text('consent_cookie_description')->default('Cookie anonim digunakan untuk mencegah modul yang sama dinilai dua kali.');
$table->unsignedSmallInteger('consent_estimated_minutes')->default(10);
$table->text('consent_withdrawal_description')->default('Partisipasi sukarela dan dapat dihentikan sebelum jawaban dikirim.');
$table->string('research_contact')->default('peneliti@example.test');
$table->string('quality_rules_version')->default('quality-rules-v1');
$table->boolean('identical_answers_flag_enabled')->default(true);
```

Cast the minutes to integer and the flag to boolean in `EvaluationPeriod`.

- [ ] **Step 4: Seed complete consent and quality configuration**

Make `consent_text` explicitly state the research purpose. Seed all new fields with Indonesian copy that does not promise anonymity beyond the token/cookie control. Keep `peneliti@example.test` clearly documented as local/UAT data that must be changed to the approved research contact before production activation.

- [ ] **Step 5: Add fields to Study Settings and respondent consent view**

Add Livewire properties, validation, persistence, and disabled-when-locked controls for every new field. Render the consent sections as headings or definition-list entries; do not concatenate and parse one blob at runtime.

- [ ] **Step 6: Extend readiness and quality flag behavior**

Readiness must reject empty structured consent fields, minutes below 1, threshold below 1 second, empty rules version, and a disabled identical-answer rule. Update `QualityFlagger`:

```php
'identical_answers' => $submission->period->identical_answers_flag_enabled
    && $answers->count() === 26
    && $answers->pluck('raw_score')->unique()->count() === 1,
```

- [ ] **Step 7: Run migration and focused tests**

Run: `php artisan migrate`

Run: `php artisan test tests/Feature/Study/StudySeedTest.php tests/Feature/Study/PeriodActivationTest.php tests/Feature/Survey/ConsentScreenerTest.php tests/Feature/Quality/QualityFlaggerTest.php`

Expected: migration succeeds and all selected tests pass.

- [ ] **Step 8: Commit structured readiness configuration**

```bash
git add application/database/migrations/2026_08_06_000014_add_release_one_readiness_fields_to_evaluation_periods.php application/app/Models/EvaluationPeriod.php application/database/seeders/WongReangStudySeeder.php application/app/Livewire/Admin/StudySettings.php application/resources/views/livewire/admin/study-settings.blade.php application/resources/views/livewire/survey/consent-screener.blade.php application/app/Domain/Study/PeriodReadinessService.php application/app/Domain/Quality/QualityFlagger.php application/tests/Feature/Study/PeriodActivationTest.php application/tests/Feature/Quality/QualityFlaggerTest.php
git commit -m "feat: make consent and quality readiness explicit"
```

---

### Task 4: Store auditable operational readiness evidence

**Files:**
- Create: `application/database/migrations/2026_08_06_000015_create_period_readiness_evidence_table.php`
- Create: `application/app/Domain/Study/ReadinessEvidenceKind.php`
- Create: `application/app/Models/PeriodReadinessEvidence.php`
- Create: `application/app/Application/Study/RecordReadinessEvidence.php`
- Create: `application/tests/Feature/Study/ReadinessEvidenceTest.php`
- Modify: `application/app/Models/EvaluationPeriod.php`
- Modify: `application/app/Domain/Study/PeriodReadinessService.php`
- Modify: `application/app/Livewire/Admin/StudySettings.php`
- Modify: `application/resources/views/livewire/admin/study-settings.blade.php`
- Modify: `application/tests/Feature/Study/PeriodActivationTest.php`

**Interfaces:**
- Consumes: authenticated admin, draft period, evidence kind, reference, and notes.
- Produces: `RecordReadinessEvidence::handle(EvaluationPeriod $period, User $actor, ReadinessEvidenceKind $kind, string $reference, string $notes): PeriodReadinessEvidence`.

- [ ] **Step 1: Write failing evidence and activation tests**

Create one test for each enum case `Https`, `BackupRestore`, and `SubmitTest`. Assert evidence requires an authenticated, email-verified, 2FA-confirmed single admin and can only be recorded while the period is draft. Assert activation remains blocked until all three records exist.

- [ ] **Step 2: Run tests and confirm missing schema/classes**

Run: `php artisan test tests/Feature/Study/ReadinessEvidenceTest.php tests/Feature/Study/PeriodActivationTest.php`

Expected: FAIL because evidence storage does not exist.

- [ ] **Step 3: Create enum, table, and model**

Use this enum:

```php
enum ReadinessEvidenceKind: string
{
    case Https = 'https';
    case BackupRestore = 'backup_restore';
    case SubmitTest = 'submit_test';
}
```

Create the table with `evaluation_period_id`, `kind`, `reference`, `notes`, `verified_by`, `verified_at`, timestamps, and a unique index on period plus kind. Use `restrictOnDelete()` for the verifier and `cascadeOnDelete()` for the period.

- [ ] **Step 4: Implement evidence recording rules**

Trim reference and notes and reject empty values. For `Https`, require `filter_var($reference, FILTER_VALIDATE_URL)` and an `https` scheme. For backup/restore and submit-test evidence, require notes of at least 20 characters so the record explains what was verified. Use `updateOrCreate()` only while the period is draft so evidence can be corrected before activation.

- [ ] **Step 5: Extend readiness with admin and evidence checks**

Define a ready admin as exactly one user with non-null `email_verified_at`, `two_factor_secret`, and `two_factor_confirmed_at`. Add separate actionable readiness issues for missing admin, HTTPS evidence, backup/restore evidence, and submit-test evidence.

- [ ] **Step 6: Add evidence controls to Study Settings**

Render the three evidence types, current verifier/time/reference, and form inputs for reference and notes. The HTTPS example must be `https://survei.wongreang.example`; backup reference example `ueq_saw_20260806_1200.sql`; submit reference example `SurveyHappyPathTest 1 test / 8 assertions`. Disable mutation after activation.

- [ ] **Step 7: Update existing activation fixtures**

In `tests/Pest.php`, create a `releaseOneReadyAdminAndEvidence(EvaluationPeriod $period): User` helper that builds the single verified/2FA-confirmed admin and all three evidence records. Replace ad hoc bypasses in `PeriodActivationTest`; do not weaken `PeriodReadinessService` in the testing environment.

- [ ] **Step 8: Run migration and readiness tests**

Run: `php artisan migrate`

Run: `php artisan test tests/Feature/Study/ReadinessEvidenceTest.php tests/Feature/Study/PeriodActivationTest.php tests/Feature/Console/CreateAdminTest.php`

Expected: all selected tests pass.

- [ ] **Step 9: Commit operational evidence support**

```bash
git add application/database/migrations/2026_08_06_000015_create_period_readiness_evidence_table.php application/app/Domain/Study/ReadinessEvidenceKind.php application/app/Models/PeriodReadinessEvidence.php application/app/Application/Study/RecordReadinessEvidence.php application/tests/Feature/Study/ReadinessEvidenceTest.php application/tests/Pest.php application/app/Models/EvaluationPeriod.php application/app/Domain/Study/PeriodReadinessService.php application/app/Livewire/Admin/StudySettings.php application/resources/views/livewire/admin/study-settings.blade.php application/tests/Feature/Study/PeriodActivationTest.php
git commit -m "feat: require auditable release readiness evidence"
```

---

### Task 5: Fingerprint and continuously verify locked study configuration

**Files:**
- Create: `application/database/migrations/2026_08_06_000016_add_configuration_hash_to_evaluation_periods.php`
- Create: `application/app/Domain/Study/StudyConfigurationHasher.php`
- Create: `application/tests/Feature/Study/StudyConfigurationHasherTest.php`
- Modify: `application/app/Models/EvaluationPeriod.php`
- Modify: `application/app/Domain/Study/PeriodReadinessService.php`
- Modify: `application/app/Domain/Study/SurveyPeriodGate.php`
- Modify: `application/tests/Feature/Study/SurveyPeriodGateTest.php`
- Modify: `application/tests/Feature/Study/PeriodActivationTest.php`
- Modify: `application/tests/Pest.php`

**Interfaces:**
- Consumes: period configuration, 13 active units, 26 instrument items, and six verified benchmarks.
- Produces: `StudyConfigurationHasher::hash(EvaluationPeriod $period): string`, stored as `evaluation_periods.configuration_hash` during activation.

- [ ] **Step 1: Write deterministic-hash and tamper tests**

Seed the study and assert two consecutive hashes are identical. Change one target, unit name, item polarity, and benchmark threshold in separate tests and assert every change produces a different hash. Activate a period, mutate one locked value directly, and assert the public survey gate rejects it with `Konfigurasi periode berubah setelah dikunci.`

- [ ] **Step 2: Run tests and confirm missing hasher failures**

Run: `php artisan test tests/Feature/Study/StudyConfigurationHasherTest.php tests/Feature/Study/SurveyPeriodGateTest.php`

Expected: FAIL because the hasher and database column do not exist.

- [ ] **Step 3: Add configuration hash storage**

Add a nullable fixed-length 64-character `configuration_hash` column after `configuration_locked_at`. It remains null for draft periods and is written in the same activation transaction as status and lock timestamp.

- [ ] **Step 4: Implement canonical hashing**

Build one array with explicitly named keys. Include dates, age/targets, every consent field, quality fields, instrument version/source, active units ordered by `display_order`, items ordered by `order`, and benchmarks ordered by scale. Normalize dates to UTC ISO-8601 and decimals to four decimal places. Encode with `JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION`, then return `hash('sha256', $json)`.

Do not include status, `configuration_locked_at`, `configuration_hash`, timestamps, evidence records, or submission/calculation data.

- [ ] **Step 5: Store the hash atomically on activation**

Inside `PeriodReadinessService::activate()`, compute the hash after row locks and readiness validation, then update:

```php
[
    'status' => PeriodStatus::Active,
    'configuration_locked_at' => now(),
    'configuration_hash' => $this->hasher->hash($lockedPeriod),
]
```

- [ ] **Step 6: Validate fingerprint in SurveyPeriodGate**

Reject null hashes and compare with `hash_equals($period->configuration_hash, $hasher->hash($period))`. Keep the comparison in the central gate so entry, page navigation, and direct submit all share it.

- [ ] **Step 7: Upgrade shared survey fixtures to valid locked fingerprints**

In `tests/Pest.php`, create the period as draft, create its units/items and other fixture data, compute the canonical hash, then update the period to active with `configuration_locked_at` and `configuration_hash`. Do not hard-code a fake 64-character hash because the gate must detect fixture drift.

- [ ] **Step 8: Run migration and lock tests**

Run: `php artisan migrate`

Run: `php artisan test tests/Feature/Study/StudyConfigurationHasherTest.php tests/Feature/Study/SurveyPeriodGateTest.php tests/Feature/Study/PeriodActivationTest.php tests/Feature/Survey/SubmitSurveyTest.php`

Expected: all selected tests pass.

- [ ] **Step 9: Commit fingerprint enforcement**

```bash
git add application/database/migrations/2026_08_06_000016_add_configuration_hash_to_evaluation_periods.php application/app/Domain/Study/StudyConfigurationHasher.php application/tests/Feature/Study/StudyConfigurationHasherTest.php application/tests/Pest.php application/app/Models/EvaluationPeriod.php application/app/Domain/Study/PeriodReadinessService.php application/app/Domain/Study/SurveyPeriodGate.php application/tests/Feature/Study/SurveyPeriodGateTest.php application/tests/Feature/Study/PeriodActivationTest.php
git commit -m "feat: fingerprint locked study configuration"
```

---

### Task 6: Add traceability metadata to raw CSV and XLSX exports

**Files:**
- Modify: `application/app/Application/Reporting/RawSurveyExport.php`
- Modify: `application/tests/Feature/Admin/RawSurveyExportTest.php`
- Modify: `application/tests/Unit/Reporting/RawSurveyExportRowTest.php`

**Interfaces:**
- Consumes: `EvaluationPeriod`, a single export timestamp, and existing raw submission rows.
- Produces: identical traceability columns in CSV and XLSX: `period_id`, `period_slug`, `period_name`, `period_status`, and `exported_at`.

- [ ] **Step 1: Write failing XLSX and CSV metadata tests**

For XLSX, assert headers `A1:AN1` include the five metadata columns, `instrument_version`, and all 26 item columns. Assert row 2 values match the requested period and `exported_at` is valid ISO-8601. For CSV, parse `streamedContent()` with `str_getcsv()` and assert the same headers and values.

- [ ] **Step 2: Run export tests and prove metadata is absent**

Run: `php artisan test tests/Feature/Admin/RawSurveyExportTest.php`

Expected: FAIL because current exports start at `submission_id` and CSV is not covered.

- [ ] **Step 3: Add stable metadata columns**

Capture `$exportedAt = now()->toIso8601String()` once per `spreadsheet()` invocation. Prefix every row with period ID, slug, name, status value, and the captured timestamp. Keep `respondent_code` pseudonymous and continue excluding token hash, profile demographics, cookie, IP, and idempotency key.

- [ ] **Step 4: Verify both formats and privacy**

Run: `php artisan test tests/Feature/Admin/RawSurveyExportTest.php tests/Unit/Reporting/RawSurveyExportRowTest.php`

Expected: XLSX and CSV tests pass and neither output contains `token_hash`, `idempotency_key`, `age`, or `anonymous_respondent_id`.

- [ ] **Step 5: Commit traceable raw exports**

```bash
git add application/app/Application/Reporting/RawSurveyExport.php application/tests/Feature/Admin/RawSurveyExportTest.php application/tests/Unit/Reporting/RawSurveyExportRowTest.php
git commit -m "feat: add period metadata to raw exports"
```

---

### Task 7: Close the missing Release One behavioral and browser coverage

**Files:**
- Modify: `application/tests/Feature/Survey/SubmitSurveyTest.php`
- Modify: `application/tests/Feature/Survey/UnitChooserTest.php`
- Modify: `application/tests/Feature/Survey/UeqWizardTest.php`
- Modify: `application/tests/Browser/OfflineDraftTest.php`
- Modify: `application/tests/Browser/SurveyHappyPathTest.php`

**Interfaces:**
- Consumes: Tasks 1–6 behavior.
- Produces: automated evidence for new sessions, transaction rollback, closed-period rejection, successful draft cleanup, keyboard-visible controls, and both raw export formats.

- [ ] **Step 1: Test the same token on another module and a later session**

Complete unit A, travel 31 minutes, select unit B with the same token, and assert two `SurveySession` rows exist while each unit has exactly one submission. Assert reopening unit A remains forbidden.

- [ ] **Step 2: Test rollback after answer insertion failure**

Temporarily register a listener for `eloquent.creating: App\\Models\\SurveyAnswer` that throws `RuntimeException`. Call the submit action in `try/finally`, forget the listener, and assert zero submissions, zero answers, and unchanged `submitted_count`.

- [ ] **Step 3: Test a period closing after wizard mount**

Mount the wizard, fill 26 valid answers, change the period to `closed`, submit, and assert the component rejects the request and creates no rows.

- [ ] **Step 4: Verify local draft cleanup after server confirmation**

Extend `SurveyHappyPathTest` to inspect localStorage after the success page loads:

```php
->assertScript("Object.keys(localStorage).every((key) => ! key.startsWith('ueq-draft-v1:'))");
```

- [ ] **Step 5: Verify offline retry and keyboard semantics**

In `OfflineDraftTest`, dispatch `online` after the existing offline assertion, confirm the submit control becomes enabled, and verify the selected answer remains. In the happy-path browser test, use Playwright keyboard Tab navigation and assert focus reaches the experience checkbox, a UEQ radio label/input, navigation button, and submit button with visible focus classes.

- [ ] **Step 6: Run the complete Release One focused suite**

Run:

```bash
php artisan test \
  tests/Feature/Auth/PublicRegistrationDisabledTest.php \
  tests/Feature/Console/CreateAdminTest.php \
  tests/Feature/Study/StudySeedTest.php \
  tests/Feature/Study/PeriodActivationTest.php \
  tests/Feature/Study/SurveyPeriodGateTest.php \
  tests/Feature/Study/StudyConfigurationHasherTest.php \
  tests/Feature/Study/ReadinessEvidenceTest.php \
  tests/Feature/Survey \
  tests/Feature/Admin/DashboardTest.php \
  tests/Feature/Admin/RawSurveyExportTest.php
```

Run browser tests separately to isolate Playwright lifecycle:

```bash
php artisan test tests/Browser/SurveyHappyPathTest.php
php artisan test tests/Browser/OfflineDraftTest.php
```

Expected: every command exits 0.

- [ ] **Step 7: Commit Release One coverage**

```bash
git add application/tests/Feature/Survey application/tests/Browser/SurveyHappyPathTest.php application/tests/Browser/OfflineDraftTest.php
git commit -m "test: close release one acceptance coverage"
```

---

### Task 8: Restore the repository-wide static-analysis gate without changing behavior

**Files:**
- Modify: `application/app/Application/Reporting/AggregateReportExport.php`
- Modify: `application/app/Application/Reporting/AggregateReportQuery.php`
- Modify: `application/app/Domain/Sensitivity/SensitivityCalculator.php`
- Modify: `application/app/Models/ExpertJudgment.php`
- Modify: `application/app/Models/SensitivityResult.php`

**Interfaces:**
- Consumes: existing Rilis 2/3 behavior and Larastan configuration.
- Produces: `composer types:check` with zero findings; no output values or persisted data may change.

- [ ] **Step 1: Capture the current 25 findings**

Run: `composer types:check`

Expected: FAIL with 25 findings in the five files listed above.

- [ ] **Step 2: Correct aggregate export model knowledge**

For properties immediately followed by `??`, use PHP's null-coalescing property access (`$data->latestRun->id ?? '-'`) instead of the redundant nullsafe operator. Keep `AggregateReportData::$latestRun` nullable. Ensure `CalculationRun::$calculated_at` and `$official_locked_at` are documented as `CarbonInterface|null`, and keep casts as `datetime`. Do not cast date strings inside the export.

- [ ] **Step 3: Add precise relationship generics**

Add relationship PHPDoc such as:

```php
/** @return BelongsTo<CalculationRun, $this> */
public function calculationRun(): BelongsTo
```

Use matching `EvaluationUnit` and `User` generic annotations for every relationship in `ExpertJudgment` and `SensitivityResult`.

- [ ] **Step 4: Type AggregateReportQuery callbacks explicitly**

Import `UeqResult`, `SawResult`, `SensitivityResult`, `ExpertJudgment`, and `EloquentCollection`. Give grouped-collection callbacks explicit parameter and array-shape return types. Check `first()` for null before accessing the unit; throw `LogicException` if a persisted result lacks its required relation rather than hiding it with a cast or suppression.

- [ ] **Step 5: Replace the sensitivity scenario mixed config array**

Represent scenarios as a typed list of pairs and destructure it:

```php
$scenarios = [
    [SensitivityScenario::S0, $consensusWeights],
    [SensitivityScenario::S1, SensitivityScenario::S1->fixedWeights()],
    [SensitivityScenario::S2, SensitivityScenario::S2->fixedWeights()],
];

foreach ($scenarios as [$scenarioEnum, $weights]) {
    $key = $scenarioEnum->value;
    // existing calculation remains unchanged
}
```

Make `fixedWeights()` return the exact non-null array shape for S1/S2, or provide a scenario method that returns the resolved weights; do not use inline `@var`, ignores, baselines, or behavior-changing casts.

- [ ] **Step 6: Run static analysis and affected tests**

Run: `composer types:check`

Run: `php artisan test tests/Unit/Sensitivity/SensitivityCalculatorTest.php tests/Feature/Admin/ReportsTest.php tests/Feature/Admin/AggregateReportExportTest.php tests/Feature/Admin/ExpertJudgmentTest.php`

Expected: PHPStan reports zero findings and all affected tests pass.

- [ ] **Step 7: Commit static-analysis corrections**

```bash
git add application/app/Application/Reporting/AggregateReportExport.php application/app/Application/Reporting/AggregateReportQuery.php application/app/Domain/Sensitivity/SensitivityCalculator.php application/app/Models/ExpertJudgment.php application/app/Models/SensitivityResult.php
git commit -m "fix: restore repository static analysis gate"
```

---

### Task 9: Re-run operational readiness and publish fresh Release One evidence

**Files:**
- Modify: `application/docs/release-1-runbook.md`

**Interfaces:**
- Consumes: completed Tasks 1–8, local MySQL 8, verified admin, Playwright Chromium, and private backup storage.
- Produces: current migration, seed, test, browser, build, backup/restore, 2FA, dashboard, export, and activation evidence without secrets.

- [ ] **Step 1: Run the full automated gate**

Run:

```bash
composer test
npm run build
php artisan migrate:status
php artisan test tests/Browser/SurveyHappyPathTest.php
php artisan test tests/Browser/OfflineDraftTest.php
```

Expected: every command exits 0; record exact test/assertion totals from output.

- [ ] **Step 2: Prepare the local UAT admin and research configuration**

Run `php artisan app:create-admin peneliti@example.test`, enroll and confirm TOTP through `/settings/security`, then update `research_contact` to the approved research contact before any production activation. Through Study Settings, verify the instrument source and all six benchmark rows.

- [ ] **Step 3: Create and restore a private MySQL backup**

Create a directory readable only by the current operator:

```bash
install -d -m 700 application/storage/app/backups
mysqldump --single-transaction --routines --triggers -u ueq_saw_app -p ueq_saw > application/storage/app/backups/ueq_saw_release_one_uat.sql
chmod 600 application/storage/app/backups/ueq_saw_release_one_uat.sql
```

Provision `ueq_saw_restore_operator` out of band, recreate only the dedicated `ueq_saw_restore` database, restore the dump, and verify migration, period, module, item, benchmark, submission, and answer counts. Never put a password in the command line or documentation.

- [ ] **Step 4: Record readiness evidence through the admin UI**

Record:

- HTTPS reference: the approved `https://` survey URL.
- Backup/restore reference: `ueq_saw_release_one_uat.sql` with verified restore counts in notes.
- Submit-test reference: fresh `SurveyHappyPathTest` command with test/assertion totals.

Confirm the readiness panel has no issues before activation.

- [ ] **Step 5: Execute manual 360-pixel UAT**

Verify consent sections, eligible and ineligible flows, 26 answers, completed-module state, another module with the same token, rest recommendation after the third module, offline recovery, dashboard count separation, CSV/XLSX metadata, keyboard focus, and screen-reader-visible error placement. Record only aggregate counts and UI outcomes.

- [ ] **Step 6: Activate and verify the locked fingerprint**

Activate through Study Settings. Confirm status `active`, `configuration_locked_at` is present, `configuration_hash` is a 64-character lowercase hexadecimal value, the survey opens inside its date window, and an attempted locked-field edit is rejected.

- [ ] **Step 7: Replace stale runbook evidence**

Update `release-1-runbook.md` with the current date, commit hash, MySQL version, migration count, test/assertion totals, backup reference, restored row counts, browser viewport, and UAT results. Remove the old blocked 2FA entry only after enrollment and dashboard/export UAT actually pass.

- [ ] **Step 8: Verify documentation contains no secret or unfinished gate**

Run:

```bash
rg -n "DB_PASSWORD|SURVEY_TOKEN_KEY|ueq_survey_token=|Blocked|remaining release-gate|\[ \]" application/docs/release-1-runbook.md
git diff --check
git status --short
```

Expected: the first command returns no secret values, blocked status, unfinished release-gate statement, or unchecked item; `git diff --check` exits 0.

- [ ] **Step 9: Commit fresh operational evidence**

```bash
git add application/docs/release-1-runbook.md
git commit -m "docs: record fresh release one readiness evidence"
```

---

## Final Verification Matrix

Before declaring Rilis 1 complete, record evidence for every row:

| Requirement | Automated evidence | Operational evidence |
|---|---|---|
| Eligible respondent completes 26 items | `SurveyHappyPathTest` | Mobile UAT submission count |
| Ineligible respondent cannot enter wizard | `ConsentScreenerTest` and `UnitChooserTest` | Ineligible UI route |
| Eligibility is stored once | immutable eligibility feature tests | Same-token rescreen check |
| Same token cannot repeat one module | unique-constraint and submit tests | Completed module disabled |
| Same token can evaluate another module/session | chooser/session feature test | Second-module UAT |
| Double submit is idempotent | `SubmitSurveyTest` | No duplicate row count |
| Failed insert is atomic | forced answer-insert failure test | No partial row count |
| Offline answers recover and clear after success | both browser tests | Offline/online mobile UAT |
| Period window and locked fingerprint enforced | gate and hash tests | Entry inside window; edit rejected |
| Admin dashboard separates counts | `DashboardTest` | Dashboard UAT after 2FA |
| CSV/XLSX are private and traceable | export feature tests | Download/open both formats |
| Activation prerequisites are auditable | readiness evidence tests | Three evidence records plus verified admin |
| Backup can be restored | migration/schema tests | Dedicated restore database counts |
| Repository gate is green | `composer test`, browser tests, `npm run build` | Runbook with current commit and totals |

## Plan Self-Review Result

- Spec coverage: every audited Rilis 1 gap maps to Tasks 1–9 and the final matrix.
- Scope: no new Rilis 2/3 feature is introduced; Task 8 only restores the existing repository gate.
- Placeholder scan: commands, files, interfaces, validation messages, evidence kinds, and local UAT values are explicit.
- Type consistency: `SurveyPeriodGate`, `RecordRespondentEligibility`, `ReadinessEvidenceKind`, `RecordReadinessEvidence`, and `StudyConfigurationHasher` use one stable signature throughout the plan.
- Safety: credentials remain interactive/out of band; backup and restore targets are narrowly scoped and documented.
