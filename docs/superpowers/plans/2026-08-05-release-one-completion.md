# Release 1 Completion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the concrete Release 1 blockers: period/unit wizard binding, browser proof, MySQL study data, backup/restore evidence, and mobile UAT evidence.

**Architecture:** Evaluation units are globally seeded reference data and do not belong to an evaluation period. The wizard route must therefore opt out of Laravel's implicit scoped child binding instead of inventing an incorrect model relation. Operational evidence is produced from the local MySQL instance without printing credentials.

**Tech Stack:** Laravel 13, Livewire 4, Pest Browser, MySQL 8, PowerShell.

## Global Constraints

- Preserve the existing uncommitted application changes and never stage the two root DOCX files.
- Keep database credentials out of shell output, documentation, Git, and chat.
- Use the existing automated browser test as the end-to-end critical-path proof at a 360 x 800 viewport.
- Do not mark Release 1 complete unless browser, MySQL, restore, and UAT evidence is fresh.

---

### Task 1: Repair global-unit wizard route binding

**Files:**
- Modify: `application/tests/Feature/Survey/UeqWizardTest.php`
- Modify: `application/app/Models/EvaluationUnit.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: active period, eligible respondent cookie, and globally seeded/evaluated unit.
- Produces: `survey.wizard` resolves a unit by its global `code` without calling `EvaluationPeriod::units()`.

- [x] **Step 1: Add a route-level regression test**

```php
it('opens a global unit from the period scoped wizard route', function () {
    $fixture = surveyFixture();

    $this->withCookie('ueq_survey_token', $fixture->plainToken)
        ->get(route('survey.wizard', ['period' => $fixture->period, 'unit' => $fixture->unit]))
        ->assertOk()
        ->assertSee('Langkah 1 dari 4');
});
```

- [x] **Step 2: Run the regression test and confirm the current binding error**

Run: `php artisan test tests/Feature/Survey/UeqWizardTest.php --filter="opens a global unit"`

Expected: fail because Laravel attempts `EvaluationPeriod::units()` while resolving `{unit:code}`.

- [x] **Step 3: Use the unit model route key and remove the custom child binding field**

```php
// EvaluationUnit.php
public function getRouteKeyName(): string
{
    return 'code';
}

// routes/web.php
Route::get('/s/wong-reang/{period:slug}/units/{unit:code}', UeqWizard::class)
    ->withoutScopedBindings()
    ->name('survey.wizard');
```

The actual route URI must be `/s/wong-reang/{period:slug}/units/{unit}`. A
custom `{unit:code}` binding field triggers Laravel/Livewire child scoping even
when `withoutScopedBindings()` is present; `EvaluationUnit::getRouteKeyName()`
keeps the generated URL and global code lookup while removing that trigger.

- [x] **Step 4: Run the focused feature and browser tests**

Run: `php artisan test tests/Feature/Survey/UeqWizardTest.php` and `php artisan test tests/Browser/SurveyHappyPathTest.php`.

Expected: both pass.

### Task 2: Prepare local MySQL research data and restore proof

**Files:**
- Modify: `application/docs/release-1-runbook.md`

**Interfaces:**
- Consumes: MySQL runtime schema, `WongReangStudySeeder`, and `mysqldump`/`mysql` clients.
- Produces: one seeded study configuration and a restore database with recorded table counts.

- [x] **Step 1: Seed the existing local MySQL database**

Run: `php artisan db:seed --class=Database\\Seeders\\WongReangStudySeeder`.

Expected: one draft period, 13 units, 26 items, and six benchmarks.

- [x] **Step 2: Create a timestamped backup and restore it to `ueq_saw_restore`**

Use the scoped local application account and existing local credential source. Confirm the database exists before replacing it, run `mysqldump --single-transaction --routines --triggers`, restore into the dedicated restore database, and query migration/submission/answer counts without emitting secrets.

- [x] **Step 3: Record fresh restore evidence**

Replace obsolete toolchain-blocked notes in the runbook with date, backup filename pattern, result, and row counts.

### Task 3: Execute local mobile UAT and release verification

**Files:**
- Modify: `application/docs/release-1-runbook.md`

**Interfaces:**
- Consumes: browser critical-path test and local application at 360 x 800.
- Produces: UAT evidence for consent, 26 responses, duplicate prevention, completed-module state, rest message, dashboard/export privacy, and a final verification record.

- [x] **Step 1: Run the 360 x 800 browser test**

Run: `php artisan test tests/Browser/SurveyHappyPathTest.php`.

- [ ] **Step 2: Exercise the remaining UAT checks through local test coverage and browser UI**

Completed for the connection/draft scenario by `tests/Browser/OfflineDraftTest.php`:
it stores a selected response locally, dispatches the browser offline event,
checks the visible interruption notice, reloads the same browser context, and
confirms the selected response is restored. The remaining operator action is
to enroll and confirm TOTP 2FA for the real administrator; the admin route
correctly redirects an unenrolled user to Security settings.

Verify connection-recovery UI, double-submit idempotency, completed module UI, three-module rest text, dashboard count separation, and raw export headers/privacy. Record results and evidence paths.

- [ ] **Step 3: Run final release verification**

Run: `composer test`, `npm run build`, `php artisan migrate:status`, and the browser test. Record the results in the runbook.
