# UEQ-SAW Release 2 UI Browser Gap Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup tiga gap UI browser Rilis 2 pada halaman Kalkulasi: accessible name kontrol Expert Judgment, akses keyboard ke tabel horizontal, dan horizontal overflow pada viewport 360 piksel.

**Architecture:** Perbaikan dibatasi pada semantic HTML dan utility responsif Tailwind di view Livewire Kalkulasi. Pest Browser/Playwright menjadi regression contract melalui fixture Rilis 2 yang sudah ada; service, state Livewire, schema database, calculation run, dan rumus UEQ/SAW tidak berubah. Setiap gap ditutup dengan siklus red-green dan commit terpisah sebelum seluruh gate Rilis 2 dijalankan kembali.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4, Flux UI 2, Tailwind CSS 4, Pest 5, Pest Browser 5, Playwright/Chromium, dan Vite 8.

## Design Authority

- Sumber kebenaran: `docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md`, khususnya §14.2 halaman Kalkulasi, §14.4 aksesibilitas, §17.4 uji navigasi keyboard/pembacaan label, dan §18.2 kriteria penerimaan Rilis 2.
- Baseline implementasi: `resources/views/livewire/admin/calculations.blade.php` pada commit `ea0c97c`.
- Baseline otomatis: `php artisan test tests/Browser` lulus dengan 6 test dan 55 assertion.
- Bukti audit browser 1280×800: flow Dashboard → Respons → Informan → Kalkulasi → preview berfungsi, tanpa error JavaScript dan tanpa gambar rusak.
- Bukti audit Axe: kontrol `selectedUnitId` dan `operationalOrder` tidak mempunyai accessible name; dua region tabel horizontal yang benar-benar overflow tidak dapat difokuskan dengan keyboard.
- Bukti audit 360×800: `window.innerWidth` bernilai 360 tetapi `document.documentElement.scrollWidth` bernilai 488 setelah preview; grup aksi header melebar sampai koordinat kanan 488.

## Global Constraints

- Pertahankan Laravel 13, Livewire 4, Flux UI 2, Tailwind CSS 4, Pest Browser 5, dan Playwright yang sudah terpasang; jangan menambah dependency.
- Halaman Kalkulasi tetap memuat preview UEQ, gap, SAW, sensitivitas, riwayat run, dan Expert Judgment seperti ditetapkan §14.2.
- Fokus keyboard harus terlihat dan label kontrol harus dapat dibaca assistive technology sesuai §14.4 dan §17.4.
- Validasi responsif wajib memakai browser nyata pada viewport tepat 360×800 dan regression viewport 1280×800.
- Tidak mengubah state, method, property, validasi, atau event Livewire: `runPreview`, `lockOfficial`, `saveExpertJudgment`, `selectedUnitId`, `operationalOrder`, dan `expertReason` tetap sama.
- Tidak mengubah service, model, migration, fixture angka, algoritma UEQ/SAW, input hash, calculation snapshot, atau persistence result.
- Label dan `aria-label` memakai bahasa Indonesia yang stabil dan menjelaskan isi region, bukan bentuk visualnya.
- Region yang dapat difokuskan harus memiliki focus ring yang terlihat; jangan menghilangkan outline tanpa pengganti.
- Horizontal scrolling tabel tetap lokal pada container tabel; halaman secara keseluruhan tidak boleh memiliki horizontal overflow pada 360 piksel.
- Test memakai `Tests\Support\ReleaseTwoFixture`; jangan memakai data produksi atau menyimpan screenshot yang memuat data penelitian.
- Jangan melemahkan assertion Axe, mengabaikan rule, menambah timeout arbitrer, atau menyembunyikan overflow pada `body` sebagai pengganti memperbaiki elemen penyebab.
- Setiap task mengikuti red-green-refactor, menjalankan test terfokus, dan diakhiri commit kecil.

---

## File Structure

| Path | Responsibility |
|---|---|
| `application/resources/views/livewire/admin/calculations.blade.php` | Semantic label, focusable table regions, focus ring, dan layout action header responsif |
| `application/tests/Browser/ReleaseTwoUiAuditTest.php` | Regression browser khusus tiga gap UI Rilis 2 pada 1280×800 dan 360×800 |
| `application/tests/Browser/AdminAnalysisFlowTest.php` | Flow fungsional Rilis 2 yang harus tetap lulus tanpa perubahan behavior |
| `application/docs/release-2-runbook.md` | Bukti penutupan audit UI browser dan hasil gate terbaru |

## Acceptance Matrix

| Gap | Acceptance evidence |
|---|---|
| Accessible name kontrol Expert Judgment | Ketiga label visual terhubung dengan `for`/`id`; browser membuktikan `HTMLInputElement.labels`/`HTMLSelectElement.labels` terisi; Axe tidak lagi melaporkan `label` atau `select-name` |
| Keyboard access tabel horizontal | Seluruh container tabel Kalkulasi yang dirender memiliki `role="region"`, `tabindex="0"`, nama region unik, dan focus ring; Axe tidak lagi melaporkan `scrollable-region-focusable` |
| Overflow mobile 360 piksel | Setelah preview, `document.documentElement.scrollWidth <= window.innerWidth`; kedua tombol aksi sepenuhnya berada di dalam viewport dan tetap dapat ditekan |

---

### Task 1: Associate Expert Judgment labels with their controls

**Files:**
- Create: `application/tests/Browser/ReleaseTwoUiAuditTest.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php:231-253`

**Interfaces:**
- Consumes: Livewire properties `selectedUnitId`, `operationalOrder`, dan `expertReason` yang sudah ada.
- Produces: DOM IDs stabil `expert-unit`, `expert-operational-order`, dan `expert-reason`, masing-masing dengan tepat satu `label[for]`.
- Preserves: submit tetap melalui `wire:submit.prevent="saveExpertJudgment"`; tidak ada perubahan payload atau validasi Livewire.

- [ ] **Step 1: Write the failing browser test for explicit label association**

Create `application/tests/Browser/ReleaseTwoUiAuditTest.php`:

```php
<?php

use Tests\Support\ReleaseTwoFixture;

it('associates every expert judgment label with its control', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.calculations'))
        ->resize(1280, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Expert Judgment & Backlog Operasional')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-unit');

                return control !== null
                    && document.querySelector('label[for="expert-unit"]') !== null
                    && control.labels?.length === 1;
            })()
        JS)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-operational-order');

                return control !== null
                    && document.querySelector('label[for="expert-operational-order"]') !== null
                    && control.labels?.length === 1;
            })()
        JS)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-reason');

                return control !== null
                    && document.querySelector('label[for="expert-reason"]') !== null
                    && control.labels?.length === 1;
            })()
        JS);
});
```

- [ ] **Step 2: Run the test and verify the intended failure**

Run:

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='associates every expert judgment label with its control'
```

Expected: FAIL on the first `assertScript`; the existing labels have no `for` attribute and the controls have no matching `id`.

- [ ] **Step 3: Add explicit, stable label-control associations**

In `application/resources/views/livewire/admin/calculations.blade.php`, replace the three Expert Judgment fields with:

```blade
<div>
    <label for="expert-unit" class="block text-xs font-semibold text-zinc-700 mb-1">Pilih Modul</label>
    <select id="expert-unit" wire:model="selectedUnitId" class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Pilih Modul --</option>
        @foreach($allUnits as $unit)
            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="expert-operational-order" class="block text-xs font-semibold text-zinc-700 mb-1">Urutan Operasional (1-13)</label>
    <input id="expert-operational-order" type="number" wire:model="operationalOrder" min="1" max="13" class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
</div>
<div class="md:col-span-2">
    <label for="expert-reason" class="block text-xs font-semibold text-zinc-700 mb-1">Alasan Penyesuaian Expert Judgment</label>
    <div class="flex gap-2">
        <input id="expert-reason" type="text" wire:model="expertReason" placeholder="Contoh: Kebutuhan regulasi mendesak..." class="w-full rounded-md border-zinc-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <flux:button type="submit" variant="primary" size="sm">Simpan</flux:button>
    </div>
</div>
```

Do not add `aria-label` when the visible `<label>` can provide the accessible name; one naming mechanism avoids conflicting copy.

- [ ] **Step 4: Run the focused browser test and existing calculation component test**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='associates every expert judgment label with its control'
php artisan test tests/Feature/Admin/CalculationsTest.php
```

Expected: both commands PASS; Expert Judgment still binds and validates through the same Livewire properties.

- [ ] **Step 5: Commit the accessible form semantics**

```bash
git add application/resources/views/livewire/admin/calculations.blade.php application/tests/Browser/ReleaseTwoUiAuditTest.php
git commit -m "fix: label release two expert judgment controls"
```

### Task 2: Make horizontal result tables named keyboard regions

**Files:**
- Modify: `application/tests/Browser/ReleaseTwoUiAuditTest.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php:53-212`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php:255-284`

**Interfaces:**
- Consumes: the label associations completed in Task 1 so the full Axe serious/critical gate can pass.
- Produces: `data-release-two-scroll-region`, `role="region"`, `tabindex="0"`, a unique `aria-label`, and a visible focus ring on every horizontal table container in the Kalkulasi view.
- Preserves: table markup, columns, values, sorting, and all calculation data remain unchanged.

- [ ] **Step 1: Add a failing keyboard-region and Axe test**

Append to `application/tests/Browser/ReleaseTwoUiAuditTest.php`:

```php
it('makes every calculation table a named keyboard region', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    $page = visit(route('admin.calculations'))
        ->resize(1280, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Pooled reliability');

    $page->assertScript(<<<'JS'
        (() => {
            const regions = Array.from(document.querySelectorAll('[data-release-two-scroll-region]'))
                .filter((region) => region.offsetParent !== null);

            return regions.length === 4
                && regions.every((region) =>
                    region.getAttribute('role') === 'region'
                    && region.tabIndex === 0
                    && (region.getAttribute('aria-label')?.trim().length ?? 0) > 0
                    && region.classList.contains('focus-visible:ring-2')
                );
        })()
    JS);

    $page->script("() => document.querySelector('[data-release-two-scroll-region]').focus()");

    $page->assertScript(<<<'JS'
        (() => {
            const region = document.querySelector('[data-release-two-scroll-region]');

            return region === document.activeElement && region.matches(':focus');
        })()
    JS)
        ->assertNoAccessibilityIssues(1)
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});
```

The fixture renders four result regions: UEQ per module/scale, pooled reliability, SAW ranking, and sensitivity. The Expert Judgment history region is conditional and is covered by the same Blade attribute pattern when records exist.

- [ ] **Step 2: Run the test and verify the intended failure**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='makes every calculation table a named keyboard region'
```

Expected: FAIL because no rendered container has `data-release-two-scroll-region`, `role="region"`, or `tabindex="0"`. Before implementation, the Axe gate also reports `scrollable-region-focusable` for two overflowing tables.

- [ ] **Step 3: Add semantic region attributes and visible keyboard focus**

Replace each of the five calculation-table wrappers currently written as `<div class="overflow-x-auto">` with the following pattern, using the exact unique label listed below:

```blade
<div
    data-release-two-scroll-region
    role="region"
    tabindex="0"
    aria-label="Hasil UEQ per modul dan skala"
    class="overflow-x-auto rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2"
>
```

Apply the same attributes and classes to all five wrappers, changing only `aria-label`:

| Section | `aria-label` |
|---|---|
| Hasil UEQ per Modul & Skala | `Hasil UEQ per modul dan skala` |
| Pooled reliability | `Reliabilitas pooled lintas modul` |
| Peringkat SAW | `Peringkat SAW baseline informan` |
| Analisis Sensitivitas | `Analisis sensitivitas peringkat` |
| Tabel Expert Judgment conditional | `Backlog operasional Expert Judgment` |

Do not put `tabindex` on `<table>` itself. The scroll container owns keyboard focus so arrow/trackpad scrolling and the visible focus ring share the same boundary.

- [ ] **Step 4: Run the focused Axe test and the full UI audit file**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='makes every calculation table a named keyboard region'
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php
```

Expected: PASS with zero serious/critical Axe findings, zero JavaScript errors, and zero broken images.

If Axe reports a new rule after these changes, invoke `superpowers:systematic-debugging`, identify the exact DOM node, and add a regression assertion; do not lower the accessibility level or suppress the rule.

- [ ] **Step 5: Commit keyboard-accessible result regions**

```bash
git add application/resources/views/livewire/admin/calculations.blade.php application/tests/Browser/ReleaseTwoUiAuditTest.php
git commit -m "fix: make release two result tables keyboard accessible"
```

### Task 3: Keep calculation actions inside the 360-pixel viewport

**Files:**
- Modify: `application/tests/Browser/ReleaseTwoUiAuditTest.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php:1-13`

**Interfaces:**
- Consumes: existing Flux actions `runPreview` and `lockOfficial`.
- Produces: `data-release-two-actions` as the stable browser selector; stacked full-width actions below `sm` and the current horizontal layout from `sm` upward.
- Preserves: button copy, Livewire click handlers, variants, conditional official-lock visibility, and desktop action order.

- [ ] **Step 1: Add a failing 360×800 viewport contract**

Append to `application/tests/Browser/ReleaseTwoUiAuditTest.php`:

```php
it('keeps calculation actions and the document inside 360 pixels', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.calculations'))
        ->resize(360, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->assertVisible('[data-flux-sidebar-toggle]')
        ->press('Jalankan preview')
        ->waitForText('Input hash')
        ->assertSee('Pooled reliability')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(<<<'JS'
            (() => {
                const actions = document.querySelector('[data-release-two-actions]');
                const viewportWidth = window.innerWidth;

                return actions !== null
                    && Array.from(actions.querySelectorAll('button')).every((button) => {
                        const rect = button.getBoundingClientRect();

                        return rect.left >= 0 && rect.right <= viewportWidth;
                    });
            })()
        JS)
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});
```

- [ ] **Step 2: Run the mobile contract and verify the measured failure**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='keeps calculation actions and the document inside 360 pixels'
```

Expected: FAIL at the document-width assertion. The known baseline is viewport width 360 and document width 488; the current action group is 364 pixels wide and its right edge is at 488.

- [ ] **Step 3: Stack the title and action buttons below the `sm` breakpoint**

Replace the page header in `application/resources/views/livewire/admin/calculations.blade.php` with:

```blade
<div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <flux:heading size="xl">Kalkulasi UEQ dan SAW</flux:heading>
        <flux:text>{{ $period->name }} · Analisis Sensitivitas &amp; Penguncian Hasil Resmi</flux:text>
    </div>
    <div data-release-two-actions class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:gap-3">
        <flux:button class="w-full sm:w-auto" wire:click="runPreview" variant="primary">Jalankan preview</flux:button>
        @if($run && $run->status !== 'official')
            <flux:button class="w-full sm:w-auto" wire:click="lockOfficial" variant="filled" color="teal">Kunci Hasil Resmi (Official)</flux:button>
        @endif
    </div>
</div>
```

Do not apply `overflow-x-hidden` to `html`, `body`, or the page root. The document-width assertion must pass because the action layout fits, while wide result tables remain independently scrollable in the regions from Task 2.

- [ ] **Step 4: Verify mobile and desktop layouts in Chromium**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php --filter='keeps calculation actions and the document inside 360 pixels'
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php
php artisan test tests/Browser/AdminAnalysisFlowTest.php tests/Browser/AdminSidebarTest.php
```

Expected: all commands PASS. At 360×800 both actions are fully inside the viewport; at 1280×800 the existing horizontal header and full analysis flow remain functional.

- [ ] **Step 5: Commit the responsive calculation header**

```bash
git add application/resources/views/livewire/admin/calculations.blade.php application/tests/Browser/ReleaseTwoUiAuditTest.php
git commit -m "fix: contain release two calculation actions on mobile"
```

### Task 4: Run complete regression gates and record closure evidence

**Files:**
- Modify: `application/docs/release-2-runbook.md`
- Verify: `application/tests/Browser/ReleaseTwoUiAuditTest.php`
- Verify: `application/tests/Browser/AdminAnalysisFlowTest.php`
- Verify: all application tests and production assets

**Interfaces:**
- Consumes: the three independently passing remediation commits from Tasks 1-3.
- Produces: fresh runbook evidence tied to the implementation commit immediately before the documentation commit.
- Preserves: private database credentials, respondent identity, raw answers, cookies, tokens, and browser session data are excluded from evidence.

- [ ] **Step 1: Capture the implementation revision and verification time**

```bash
git rev-parse HEAD
TZ=Asia/Jakarta date '+%Y-%m-%d %H:%M:%S WIB'
```

Use the exact command outputs when updating the runbook; do not write credentials, absolute backup locations, test user names, or raw fixture answers.

- [ ] **Step 2: Run the dedicated UI browser gate**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php
```

Expected: 3 tests PASS with zero failures. The gate proves label association, serious/critical Axe compliance, keyboard-focusable result regions, no 360-pixel document overflow, no JavaScript errors, and no broken images.

- [ ] **Step 3: Run all browser regressions**

```bash
cd application
php artisan test tests/Browser
```

Expected: 9 tests PASS with zero failures, covering Rilis 1 survey/mobile/offline, Rilis 2 admin analysis/UI, Rilis 3 regression, and desktop/mobile sidebar behavior.

- [ ] **Step 4: Run the complete repository gates**

```bash
cd application
composer test
npm run build
cd ..
git diff --check
```

Expected:

- Pint PASS.
- PHPStan reports 0 errors.
- Pest reports 0 failed tests.
- Vite production build exits 0; the optional `fontaine` warning may remain non-blocking.
- `git diff --check` exits 0 with no output.

- [ ] **Step 5: Update the Rilis 2 runbook with exact browser evidence**

In `application/docs/release-2-runbook.md`:

1. update the verification timestamp and implementation commit using Step 1 outputs;
2. replace the browser gate row with the Step 3 test count and assertion count printed by Pest;
3. state that the calculation viewport was checked at both 1280×800 and 360×800;
4. record `document.scrollWidth <= window.innerWidth` at 360 pixels;
5. record zero serious/critical Axe findings, zero JavaScript errors, and zero broken images;
6. record that Expert Judgment labels are explicitly associated and each horizontal table is a named focusable region;
7. retain the existing privacy boundary and MySQL evidence unchanged.

- [ ] **Step 6: Re-run the evidence-bearing commands after documentation changes**

```bash
cd application
php artisan test tests/Browser/ReleaseTwoUiAuditTest.php
php artisan test tests/Browser
cd ..
git diff --check
git status --short
```

Expected: both browser commands PASS, whitespace check exits 0, and `git status --short` lists only the planned runbook and implementation/test changes not yet committed.

- [ ] **Step 7: Commit the verified UI remediation evidence**

```bash
git add application/docs/release-2-runbook.md
git commit -m "docs: record release two UI browser remediation"
```

- [ ] **Step 8: Verify the final commit and clean worktree**

```bash
git status --short
git log -4 --oneline --decorate
```

Expected: `git status --short` has no output and the four latest commits are the three focused UI fixes plus the runbook evidence commit.

## Completion Criteria

The remediation is complete only when all conditions below are true:

- [ ] `selectedUnitId`, `operationalOrder`, and `expertReason` have explicit, unique label associations.
- [ ] Axe reports no serious or critical issue on the populated Kalkulasi preview at 1280×800.
- [ ] Every rendered calculation table wrapper is a uniquely named, keyboard-focusable region with visible focus styling.
- [ ] The Kalkulasi document width does not exceed 360 pixels at a 360×800 viewport after preview.
- [ ] Both action buttons remain completely inside the mobile viewport and retain their original Livewire behavior.
- [ ] Existing functional Rilis 2 analysis flow, Rilis 3 lock flow, sidebar, survey, and offline draft browser tests still pass.
- [ ] Full Composer test, PHPStan, Pint, Vite build, and whitespace gates pass.
- [ ] Runbook records only non-sensitive, freshly measured evidence.
- [ ] No test screenshot, debug output, browser session, or temporary audit file remains in the worktree.
