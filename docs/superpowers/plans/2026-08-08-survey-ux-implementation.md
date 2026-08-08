# Sub-proyek F: Survey UX Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the public UEQ survey responsive on mobile, add a lightweight module-name summary to the completion page, and add a 360px overflow + Axe regression test.

**Architecture:** Pure Blade/CSS changes for responsiveness (Tailwind responsive prefixes), a small eager-load + blade echo for the completion-page module name, and one new Pest Browser test file mirroring the existing admin `ReleaseTwoUiAuditTest` pattern.

**Tech Stack:** Laravel 13, Livewire 4, Tailwind CSS 4, Pest 5 + `pestphp/pest-plugin-browser` (Playwright/Chromium).

## Global Constraints

- App root is `application/` (run all artisan/composer commands there).
- `assertNoAccessibilityIssues(int $level = 1)` — use `assertNoAccessibilityIssues(1)`.
- No database migrations, no API contract changes, no SAW/UEQ logic changes.
- Do not display per-item answers or per-dimension scores on the completion page (privacy — avoids leaking aggregate data).
- Helper functions available globally in tests: `surveyFixture()`, `lockStudyConfiguration()`, `completedSubmissionFixture()` (defined in `application/tests/Pest.php`).
- i18n copy is Indonesian; keep new user-facing strings in Indonesian.

---

### Task 1: Add the responsive regression test (failing first)

**Files:**
- Create: `application/tests/Browser/SurveyResponsiveTest.php`

**Interfaces:**
- Consumes: global helpers `surveyFixture()`, `lockStudyConfiguration()`; Pest Browser `visit()`, `resize()`, `assertScript()`, `assertNoAccessibilityIssues()`, `assertNoJavaScriptErrors()`, `waitForText()`, `press()`, `click()`, `check()`, `fill()`, `assertSee()`.
- Produces: a browser test that will fail on the unmodified `grid-cols-7` (overflow) and pass once Tasks 2–3 land.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Study\PeriodStatus;

it('keeps every survey page inside 360 pixels without overflow or accessibility issues', function () {
    $fixture = surveyFixture();
    $fixture->period->update([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'configuration_locked_at' => now(),
    ]);
    $fixture->unit->update(['code' => 'ibadah-yu', 'name' => 'Ibadah-Yu']);
    $fixture->period = lockStudyConfiguration($fixture->period);

    $page = visit(route('survey.entry', $fixture->period))
        ->resize(360, 800)
        ->waitForText('Informasi Penelitian')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->click('ui-checkbox[wire\\:model="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->click('ui-checkbox[wire\\:model="isIndramayuResident"]')
        ->click('ui-checkbox[wire\\:model="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih Modul')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->check('[wire\\:model="confirmedExperience"]');

    $step = 1;

    foreach (range(1, 26) as $itemOrder) {
        $page->click('label[for="ueq-item-'.$itemOrder.'-value-4"]');

        if (in_array($itemOrder, [7, 14, 20, 26], true)) {
            $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
                ->assertNoAccessibilityIssues(1);

            if ($step < 4) {
                $page->press('Berikutnya')->waitForText('Langkah '.($step + 1).' dari 4');
            }

            $step++;
        }
    }

    $page->press('Kirim Penilaian')
        ->waitForText('Penilaian berhasil disimpan')
        ->assertSee('Penilaian berhasil disimpan')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->assertNoJavaScriptErrors();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run (from `application/`): `php artisan test --filter=SurveyResponsiveTest`

Expected: FAIL — the `scrollWidth <= window.innerWidth` assertion on the wizard step 1 fails because `grid-cols-7` overflows the 360px viewport (7 × 44px cells + gaps + padding exceeds ~328px of available width).

- [ ] **Step 3: Verify the failure is the expected overflow (not a setup error)**

Read the failure output. Confirm the failing assertion is on the wizard overflow, not a navigation/selector error. If a selector error appears, fix the test before continuing.

- [ ] **Step 4: Commit (provisional — this test is expected to fail until Tasks 2–3)**

```bash
git add tests/Browser/SurveyResponsiveTest.php
git commit -m "test: add mobile overflow and axe regression test for the survey flow"
```

---

### Task 2: Make the Likert scale responsive

**Files:**
- Modify: `application/resources/views/livewire/survey/ueq-wizard.blade.php:33`

**Interfaces:**
- Consumes: none (self-contained CSS class change).
- Produces: the DOM class change that makes the Task 1 overflow assertion pass on mobile while preserving 7-across on `sm:`+.

- [ ] **Step 1: Change the grid class**

In `ueq-wizard.blade.php`, line 33, change:

```blade
<div class="grid grid-cols-7 gap-2" role="radiogroup" aria-label="Item {{ $item->order }}">
```

to:

```blade
<div class="grid grid-cols-1 gap-2 sm:grid-cols-7" role="radiogroup" aria-label="Item {{ $item->order }}">
```

Do NOT change the `range(1, 7)` loop, the `min-h-11` tap target, the `aria-label`, or the input `name`/`value`/`id` attributes — the test in Task 4 and `SurveyHappyPathTest` rely on them.

- [ ] **Step 2: Run the responsive test to verify the wizard overflow is fixed**

Run (from `application/`): `php artisan test --filter=SurveyResponsiveTest`

Expected: the wizard overflow assertion now passes. The test may still fail on the consent-card overflow (handled in Task 3) — that is expected.

- [ ] **Step 3: Run the existing happy-path test to confirm no regression**

Run (from `application/`): `php artisan test --filter=SurveyHappyPathTest`

Expected: PASS (focus/keyboard assertions still match the unchanged input markup).

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/survey/ueq-wizard.blade.php
git commit -m "feat: stack the 7-point Likert scale on mobile, keep 7 columns on desktop"
```

---

### Task 3: Make the consent stat cards responsive

**Files:**
- Modify: `application/resources/views/livewire/survey/consent-screener.blade.php:9`

**Interfaces:**
- Consumes: none (self-contained CSS class change).
- Produces: the DOM class change that makes the Task 1 consent overflow/crowding assertion stable on mobile.

- [ ] **Step 1: Change the grid class**

In `consent-screener.blade.php`, line 9, change:

```blade
<div class="grid grid-cols-3 gap-2.5">
```

to:

```blade
<div class="grid gap-2.5 sm:grid-cols-3">
```

Do NOT change the three card contents (Anonim / ±N mnt / 26 pertanyaan) or their `reveal-delay-*` classes.

- [ ] **Step 2: Run the responsive test to verify both overflows are fixed**

Run (from `application/`): `php artisan test --filter=SurveyResponsiveTest`

Expected: PASS (consent + wizard + complete overflow and Axe all green).

- [ ] **Step 3: Run the consent feature tests to confirm no regression**

Run (from `application/`): `php artisan test --filter=ConsentScreenerTest`

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/survey/consent-screener.blade.php
git commit -m "feat: stack consent stat cards on mobile, keep 3 columns on desktop"
```

---

### Task 4: Show the rated module name on the completion page

**Files:**
- Modify: `application/app/Livewire/Survey/Complete.php:22-26`
- Modify: `application/resources/views/livewire/survey/complete.blade.php:7`
- Test: `application/tests/Feature/Survey/CompletePageTest.php`

**Interfaces:**
- Consumes: `completedSubmissionFixture()` (returns object with `period`, `unit`, `respondent`, `plainToken`, `submission`); Livewire `Complete` component; `SurveySubmission::unit()` BelongsTo relation (`SurveySubmission.php:49`).
- Produces: eagerly-loaded `$submission->unit` available in the view; a new `CompletePageTest` asserting the module name appears.

- [ ] **Step 1: Write the failing feature test**

Create `application/tests/Feature/Survey/CompletePageTest.php`:

```php
<?php

use App\Livewire\Survey\Complete;
use Livewire\Livewire;

it('shows the rated module name on the completion page', function () {
    $fixture = completedSubmissionFixture();
    $fixture->unit->update(['name' => 'Ibadah-Yu']);

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(Complete::class, ['period' => $fixture->period])
        ->assertSee('Penilaian berhasil disimpan')
        ->assertSee('Ibadah-Yu');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run (from `application/`): `php artisan test --filter=CompletePageTest`

Expected: FAIL — the page does not yet render the module name (the assertion `assertSee('Ibadah-Yu')` fails).

- [ ] **Step 3: Eager-load the unit relation in Complete.php**

In `Complete.php`, modify the query (lines 22–26) to eager-load `unit`:

```php
$this->submission = SurveySubmission::query()
    ->with('unit')
    ->where('evaluation_period_id', $period->id)
    ->where('anonymous_respondent_id', $respondent->id)
    ->latest('completed_at')
    ->firstOrFail();
```

- [ ] **Step 4: Render the module name in the completion view**

In `complete.blade.php`, after the thank-you paragraph (line 7), add:

```blade
<p class="text-emerald-800">Modul yang Anda nilai: <span class="font-semibold">{{ $submission->unit->name }}</span></p>
```

- [ ] **Step 5: Run test to verify it passes**

Run (from `application/`): `php artisan test --filter=CompletePageTest`

Expected: PASS.

- [ ] **Step 6: Run the full survey browser + feature suites to confirm no regression**

Run (from `application/`):
- `php artisan test --filter=SurveyResponsiveTest`
- `php artisan test --filter=SurveyHappyPathTest`
- `php artisan test --filter=CompletePageTest`

Expected: all PASS.

- [ ] **Step 7: Commit**

```bash
git add tests/Feature/Survey/CompletePageTest.php app/Livewire/Survey/Complete.php resources/views/livewire/survey/complete.blade.php
git commit -m "feat: show the rated module name on the survey completion page"
```

---

### Task 5: Final verification of the whole sub-project

**Files:**
- None (verification only).

**Interfaces:**
- Consumes: all prior tasks.

- [ ] **Step 1: Run the complete feature and browser test suites for the survey**

Run (from `application/`):
- `php artisan test --testsuite=Feature`
- `php artisan test --testsuite=Browser`

Expected: all PASS, including the pre-existing `OfflineDraftTest`, `UnitChooserTest`, `UeqWizardTest`, `SubmitSurveyTest`, and admin browser tests.

- [ ] **Step 2: Confirm the spec's acceptance criteria**

- Likert scale: `grid-cols-1 ... sm:grid-cols-7` (mobile stacks, desktop 7-across).
- Consent cards: `grid ... sm:grid-cols-3` (mobile stacks, desktop 3-across).
- Completion page shows "Modul yang Anda nilai: {name}".
- `SurveyResponsiveTest` green (overflow 360px + Axe on consent, wizard, complete).

- [ ] **Step 3: Commit any remaining changes**

```bash
git status
git add -A
git commit -m "chore: finalize sub-project F survey UX"
```