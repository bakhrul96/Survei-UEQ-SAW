# Admin Sidebar Navigation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every working admin area discoverable through one workflow-oriented, responsive, accessible sidebar and remove the two irrelevant starter-kit links.

**Architecture:** Keep `resources/views/layouts/app/sidebar.blade.php` as the single desktop/mobile navigation source and continue using Flux sidebar primitives. Add focused feature and browser coverage before changing markup, then run the complete Release One gate so Task 9 evidence refers to the final UI.

**Tech Stack:** Laravel 13, Livewire 4, Flux 2, Blade, Pest 5, Pest Browser/Playwright, Tailwind CSS 4, Superdesign CLI.

## Global Constraints

- Work only in the linked worktree on `codex/release-one-remediation`; never implement on `main` or `master`.
- Preserve the existing route, middleware, authorization, database, and business-process behavior.
- Use only the approved labels and workflow groups; do not add disabled items, `#` links, direct export URLs, or new pages.
- Keep one sidebar markup source for desktop and mobile; do not duplicate a separate mobile menu.
- Keep Profile, Security, and Appearance under the existing settings subnavigation reached through Pengaturan Akun.
- Remove Repository and Documentation from application navigation.
- Use named Laravel routes and Flux icons that already exist in the installed package.
- Follow test-driven development: observe the focused tests fail before editing the sidebar, then make the minimum markup change that passes them.
- Do not record credentials, TOTP secrets, recovery codes, respondent tokens, or database secrets in source, tests, Superdesign context, command lines, or documentation.
- Do not activate the evaluation period as part of this sidebar feature.

## File Map

- Create: `.superdesign/init/components.md` — full source for shared project UI components used by the admin shell.
- Create: `.superdesign/init/layouts.md` — full source and descriptions for the app, sidebar, header, settings, auth, and survey layouts.
- Create: `.superdesign/init/routes.md` — route-to-page/layout map plus the full application route definitions.
- Create: `.superdesign/init/theme.md` — compact design-token summary followed by raw theme/CSS configuration.
- Create: `.superdesign/init/pages.md` — dependency trees for the key admin pages and settings pages.
- Create: `.superdesign/init/extractable-components.md` — extractable Sidebar and account-menu definitions.
- Create: `.superdesign/design-system.md` — current application visual system and responsive-shell rules.
- Modify: `.gitignore` — ignore `.superdesign/tmp/` only.
- Create: `application/tests/Feature/Admin/SidebarNavigationTest.php` — server-rendered menu, URL, removal, and current-state coverage.
- Create: `application/tests/Browser/AdminSidebarTest.php` — desktop visibility and 360-pixel drawer coverage.
- Modify: `application/resources/views/layouts/app/sidebar.blade.php` — approved groups, destinations, icons, and active states.
- Modify: `application/docs/release-1-runbook.md` — record the final sidebar/browser verification only after all gates pass.

---

### Task 1: Establish and approve the Superdesign ground truth

**Files:**
- Create: `.superdesign/init/components.md`
- Create: `.superdesign/init/layouts.md`
- Create: `.superdesign/init/routes.md`
- Create: `.superdesign/init/theme.md`
- Create: `.superdesign/init/pages.md`
- Create: `.superdesign/init/extractable-components.md`
- Create: `.superdesign/design-system.md`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: the approved design spec and the existing Blade/Flux layout source.
- Produces: six non-empty init files, one design system, an extracted Sidebar component, one faithful current-UI draft, and one approved workflow-grouped variation.

- [ ] **Step 1: Run the mandatory CLI preflight**

Run from the repository root:

```bash
npx --yes @superdesign/cli@latest
```

Expected: the command lists recent projects and prints an `auth:` line. If it reports `not authenticated`, run `npx --yes @superdesign/cli@latest login`, wait for successful browser authentication, then rerun the preflight once. Stop if login fails.

- [ ] **Step 2: Build all six mandatory init files**

Follow the Superdesign `INIT.md` instructions and use these exact source sets:

- `components.md`: include the complete contents of `application/resources/views/components/app-logo.blade.php` and `application/resources/views/components/desktop-user-menu.blade.php`; identify Flux as the shared primitive library.
- `layouts.md`: include complete contents of `application/resources/views/layouts/app.blade.php`, `application/resources/views/layouts/app/sidebar.blade.php`, `application/resources/views/layouts/app/header.blade.php`, `application/resources/views/pages/settings/layout.blade.php`, `application/resources/views/layouts/auth.blade.php`, and `application/resources/views/layouts/survey.blade.php`.
- `routes.md`: map `/admin/dashboard`, `/admin/study`, `/admin/responses`, `/admin/reports`, `/admin/calculations`, `/admin/technical-assessments`, `/settings/profile`, `/settings/security`, and `/settings/appearance`; include full contents of `application/routes/web.php` and `application/routes/settings.php`.
- `theme.md`: summarize Instrument Sans, zinc light/dark surfaces, Flux accent/current states, Tailwind spacing/radius/breakpoint behavior, then include full `application/resources/css/app.css` and `application/vite.config.js`.
- `pages.md`: record dependency trees from each admin Livewire class to its `resources/views/livewire/admin/*.blade.php` view and through `layouts.app` to `layouts.app.sidebar`; include the three settings pages through `pages/settings/layout.blade.php`.
- `extractable-components.md`: define `AdminSidebar` as a layout component sourced from `application/resources/views/layouts/app/sidebar.blade.php`, with `activeRoute` as its only variable state; define `DesktopUserMenu` separately.

Then write `.superdesign/design-system.md` with the existing app shell, typography, zinc color system, Flux component vocabulary, desktop `lg` breakpoint, 256-pixel sidebar width, mobile drawer behavior, current-state treatment, and the approved four navigation groups. Do not introduce a new style.

- [ ] **Step 3: Make temporary Superdesign artifacts uncommittable**

Add exactly this entry to the repository-root `.gitignore` if it is absent:

```gitignore
.superdesign/tmp/
```

Do not ignore `.superdesign/init/` or `.superdesign/design-system.md`; they are the reviewed design context.

- [ ] **Step 4: Verify init completeness before any design command**

Run:

```bash
for file in components.md layouts.md routes.md theme.md pages.md extractable-components.md; do
  test -s ".superdesign/init/$file" || exit 1
done
test -s .superdesign/design-system.md
```

Expected: exit 0 and no missing or empty file.

- [ ] **Step 5: Create the Superdesign project and extract the existing sidebar**

Read Superdesign `COMPONENTS.md`, create `.superdesign/tmp/admin-sidebar.html`, and convert the existing sidebar to a Petite-Vue template. Keep labels, icons, spacing, colors, and account menu hardcoded; expose only `activeRoute`.

Run:

```bash
npx --yes @superdesign/cli@latest create-project --title "UEQ SAW Admin Sidebar"
read "sidebar_project_id?Project ID printed by create-project: "
test -n "$sidebar_project_id"
npx --yes @superdesign/cli@latest list-components --project-id "$sidebar_project_id"
npx --yes @superdesign/cli@latest create-component \
  --project-id "$sidebar_project_id" \
  --name "AdminSidebar" \
  --html-file .superdesign/tmp/admin-sidebar.html \
  --description "Authenticated UEQ SAW admin navigation shell" \
  --props '[{"name":"activeRoute","type":"string","defaultValue":"admin.dashboard"}]'
```

Use the actual `projectId` printed by `create-project` in both subsequent commands. Do not invent or persist a placeholder ID in source.

- [ ] **Step 6: Create the mandatory current-UI reproduction**

Run one `create-design-draft` call with one prompt, using the actual project ID:

```bash
npx --yes @superdesign/cli@latest create-design-draft \
  --project-id "$sidebar_project_id" \
  --title "Current Admin Dashboard Sidebar" \
  -p "Create a pixel-perfect reproduction of the current authenticated admin dashboard and sidebar. Match exactly the existing 256-pixel Flux sidebar, dark zinc application shell, logo, Dashboard and Respons links, starter-kit Repository and Documentation links, account profile area, spacing, typography, icons, borders, current state, and desktop layout. Use only the supplied source and design-system tokens; do not improve or add anything." \
  --context-file .superdesign/design-system.md \
  --context-file .superdesign/init/theme.md \
  --context-file application/resources/views/layouts/app.blade.php \
  --context-file application/resources/views/layouts/app/sidebar.blade.php \
  --context-file application/resources/views/components/app-logo.blade.php \
  --context-file application/resources/views/components/desktop-user-menu.blade.php \
  --context-file application/resources/views/livewire/admin/dashboard.blade.php \
  --context-file application/resources/css/app.css
```

Expected: one draft ID, one preview URL, and one canvas URL. This call must not contain the redesigned groups.

Capture the actual draft ID printed by the command:

```bash
read "sidebar_draft_id?Draft ID printed by create-design-draft: "
test -n "$sidebar_draft_id"
```

- [ ] **Step 7: Create exactly one approved navigation variation**

Using the actual draft ID from Step 6, run:

```bash
npx --yes @superdesign/cli@latest iterate-design-draft \
  --draft-id "$sidebar_draft_id" \
  --mode branch \
  -p "Keep the current app shell, dimensions, typography, zinc palette, Flux component styling, account profile area, responsive behavior, and page content unchanged. Replace the sidebar navigation with exactly four groups: Ikhtisar containing Dashboard; Pengumpulan Data containing Pengaturan Studi, Respons, and Laporan & Ekspor; Analisis containing Perhitungan and Penilaian Teknis; Akun containing Pengaturan Akun. Give each destination a distinct existing Flux icon, preserve the active state, and remove Repository and Documentation. Use only the fonts, colors, spacing, and component styles defined in the design system. Do not introduce any new visual style." \
  --user-request "tampilkan semua halaman yang sudah berfungsi, dikelompokkan per area, tanpa item disabled" \
  --context-file .superdesign/design-system.md \
  --context-file .superdesign/init/theme.md \
  --context-file application/resources/views/layouts/app.blade.php \
  --context-file application/resources/views/layouts/app/sidebar.blade.php \
  --context-file application/resources/views/components/app-logo.blade.php \
  --context-file application/resources/views/components/desktop-user-menu.blade.php \
  --context-file application/resources/views/livewire/admin/dashboard.blade.php \
  --context-file application/resources/css/app.css
```

Expected: exactly one branched variation. Surface the returned canvas and preview URLs to the user and pause for approval before editing application code.

- [ ] **Step 8: Commit the reviewed Superdesign context after visual approval**

Run:

```bash
git add .gitignore .superdesign/init .superdesign/design-system.md
git commit -m "docs: initialize admin navigation design context"
```

Do not add `.superdesign/tmp/`.

---

### Task 2: Specify complete sidebar behavior with failing tests

**Files:**
- Create: `application/tests/Feature/Admin/SidebarNavigationTest.php`
- Create: `application/tests/Browser/AdminSidebarTest.php`

**Interfaces:**
- Consumes: existing named routes, `User::factory()`, `WongReangStudySeeder`, Flux `data-flux-sidebar*` attributes, and the shared app layout.
- Produces: a red feature contract for groups/links/current state and a red browser contract for desktop/mobile behavior.

- [ ] **Step 1: Write the feature test fixture and menu contract**

Create `application/tests/Feature/Admin/SidebarNavigationTest.php`:

```php
<?php

use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
});

function sidebarNavigationLink(TestResponse $response, string $href): DOMElement
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($response->getContent());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $link = (new DOMXPath($document))->query(
        '//a[@data-flux-sidebar-item and @href="'.$href.'"]',
    )->item(0);

    expect($link)->toBeInstanceOf(DOMElement::class);

    return $link;
}

it('renders every working application area in workflow groups', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'Ikhtisar',
            'Dashboard',
            'Pengumpulan Data',
            'Pengaturan Studi',
            'Respons',
            'Laporan &amp; Ekspor',
            'Analisis',
            'Perhitungan',
            'Penilaian Teknis',
            'Akun',
            'Pengaturan Akun',
        ], false)
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation');

    foreach ([
        'admin.dashboard',
        'admin.study-settings',
        'admin.responses',
        'admin.reports',
        'admin.calculations',
        'admin.technical-assessments',
        'profile.edit',
    ] as $routeName) {
        sidebarNavigationLink($response, route($routeName));
    }
});

it('marks the dashboard destination as current', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    expect(sidebarNavigationLink($response, route('admin.dashboard'))->hasAttribute('data-current'))->toBeTrue();
});

it('marks the account destination as current throughout account settings', function (string $routeName) {
    $response = $this->withSession(['auth.password_confirmed_at' => time()])
        ->actingAs($this->admin)
        ->get(route($routeName))
        ->assertOk();

    expect(sidebarNavigationLink($response, route('profile.edit'))->hasAttribute('data-current'))->toBeTrue();
})->with([
    'profile' => 'profile.edit',
    'security' => 'security.edit',
    'appearance' => 'appearance.edit',
]);
```

- [ ] **Step 2: Write the desktop and mobile browser contract**

Create `application/tests/Browser/AdminSidebarTest.php`:

```php
<?php

use App\Models\User;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
    $this->actingAs($this->admin);
});

it('shows the complete sidebar on desktop', function () {
    visit(route('admin.dashboard'))
        ->resize(1280, 800)
        ->assertVisible('[data-flux-sidebar]')
        ->assertVisible('a[href*="/admin/study"]')
        ->assertSee('Pengumpulan Data')
        ->assertSee('Laporan & Ekspor')
        ->assertSee('Penilaian Teknis')
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation')
        ->assertScript('document.querySelector(\'a[href*="/admin/dashboard"]\').hasAttribute(\'data-current\')');
});

it('opens the complete sidebar from the hamburger at 360 pixels', function () {
    visit(route('admin.dashboard'))
        ->resize(360, 800)
        ->assertVisible('[data-flux-sidebar-toggle]')
        ->assertAttribute('[data-flux-sidebar-toggle]', 'aria-label', 'Toggle sidebar')
        ->click('[data-flux-sidebar-toggle]')
        ->assertVisible('a[href*="/admin/study"]')
        ->assertSee('Pengaturan Studi')
        ->assertSee('Pengaturan Akun');
});
```

- [ ] **Step 3: Run the focused tests and observe the intended red state**

Run:

```bash
php artisan test tests/Feature/Admin/SidebarNavigationTest.php
php artisan test tests/Browser/AdminSidebarTest.php
```

Expected: feature and browser tests fail because Pengaturan Studi, Laporan & Ekspor, Analisis, Penilaian Teknis, Akun, and Pengaturan Akun are absent while Repository and Documentation are still rendered. If they fail for test syntax or setup instead, correct only the test harness and rerun until the failure describes missing sidebar behavior.

---

### Task 3: Implement the approved grouped sidebar

**Files:**
- Modify: `application/resources/views/layouts/app/sidebar.blade.php`
- Test: `application/tests/Feature/Admin/SidebarNavigationTest.php`
- Test: `application/tests/Browser/AdminSidebarTest.php`

**Interfaces:**
- Consumes: the red contracts from Task 2 and these named routes: `admin.dashboard`, `admin.study-settings`, `admin.responses`, `admin.reports`, `admin.calculations`, `admin.technical-assessments`, and `profile.edit`.
- Produces: one Flux sidebar used by both desktop and mobile, with four approved groups and correct current-state expressions.

- [ ] **Step 1: Replace only the sidebar navigation block**

Keep the document shell, logo, mobile collapse/toggle, account dropdown, logout form, toast group, and Flux scripts unchanged. Replace the existing primary group and delete the secondary Repository/Documentation nav. The navigation portion must be:

```blade
<flux:sidebar.nav>
    <flux:sidebar.group :heading="__('Ikhtisar')" class="grid">
        <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group :heading="__('Pengumpulan Data')" class="grid">
        <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.study-settings')" :current="request()->routeIs('admin.study-settings')" wire:navigate>
            {{ __('Pengaturan Studi') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.responses')" :current="request()->routeIs('admin.responses')" wire:navigate>
            {{ __('Respons') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-chart-bar" :href="route('admin.reports')" :current="request()->routeIs('admin.reports')" wire:navigate>
            {{ __('Laporan & Ekspor') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group :heading="__('Analisis')" class="grid">
        <flux:sidebar.item icon="calculator" :href="route('admin.calculations')" :current="request()->routeIs('admin.calculations')" wire:navigate>
            {{ __('Perhitungan') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="wrench-screwdriver" :href="route('admin.technical-assessments')" :current="request()->routeIs('admin.technical-assessments')" wire:navigate>
            {{ __('Penilaian Teknis') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group :heading="__('Akun')" class="grid">
        <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate>
            {{ __('Pengaturan Akun') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
</flux:sidebar.nav>
```

- [ ] **Step 2: Run the focused feature test**

Run:

```bash
php artisan test tests/Feature/Admin/SidebarNavigationTest.php
```

Expected: all sidebar feature tests pass.

- [ ] **Step 3: Run the focused browser test**

Run:

```bash
php artisan test tests/Browser/AdminSidebarTest.php
```

Expected: desktop sidebar and 360-pixel drawer tests pass. If the mobile link is present but Playwright reports it hidden, inspect the actual Flux collapsed attribute and correct the interaction assertion; do not add duplicated mobile markup.

- [ ] **Step 4: Commit the sidebar and its contracts**

Run:

```bash
git add application/resources/views/layouts/app/sidebar.blade.php application/tests/Feature/Admin/SidebarNavigationTest.php application/tests/Browser/AdminSidebarTest.php
git commit -m "feat: complete admin sidebar navigation"
```

---

### Task 4: Re-run the final repository and Release One gates

**Files:**
- Modify: `application/docs/release-1-runbook.md`

**Interfaces:**
- Consumes: the committed sidebar, focused contracts, existing Release One browser fixtures, and current runbook evidence.
- Produces: fresh final test/build/browser totals and an honest runbook entry without claiming unfinished HTTPS or manual UAT.

- [ ] **Step 1: Run the complete automated gate**

Run from `application/`:

```bash
composer test
npm run build
php artisan migrate:status
php artisan test tests/Browser/AdminSidebarTest.php
php artisan test tests/Browser/SurveyHappyPathTest.php
php artisan test tests/Browser/OfflineDraftTest.php
```

Expected: Pint passes, PHPStan reports zero errors, the full Pest suite passes, Vite exits 0, all 23 migrations are `Ran`, and all three browser files pass. Record exact test/assertion totals from the output instead of copying old totals.

- [ ] **Step 2: Update only verified runbook evidence**

In `application/docs/release-1-runbook.md`:

- update repository and focused test totals to the exact fresh results;
- add the Admin Sidebar desktop viewport and 360-pixel drawer result;
- note that all working admin areas are reachable through the grouped navigation;
- keep HTTPS evidence and manual production UAT explicitly pending if they have not been performed;
- do not record a secret, user email, TOTP value, recovery code, or response data.

- [ ] **Step 3: Run documentation and diff gates**

Run from the repository root:

```bash
if rg -n 'DB_PASSWORD|SURVEY_TOKEN_KEY|ueq_survey_token=|Blocked|remaining release-gate|\[ \]' application/docs/release-1-runbook.md; then
  exit 1
fi
git diff --check
git status --short
```

Expected: no forbidden runbook content, `git diff --check` exits 0, and only the intended runbook update is unstaged.

- [ ] **Step 4: Commit refreshed sidebar verification evidence**

Run:

```bash
git add application/docs/release-1-runbook.md
git commit -m "docs: record verified admin sidebar evidence"
```

- [ ] **Step 5: Recheck the worktree and Task 9 readiness boundary**

Run:

```bash
git status --short
php artisan tinker --execute='$period = App\Models\EvaluationPeriod::query()->firstOrFail(); dump(app(App\Domain\Study\PeriodReadinessService::class)->issues($period));'
```

Expected: clean worktree. The remaining readiness issues must reflect actual operational state; do not activate until HTTPS, backup/restore, submit evidence, and manual UAT requirements are genuinely satisfied.

---

## Plan Self-Review

- Spec coverage: four approved groups, seven destinations, desktop/mobile parity, active states, starter-link removal, accessibility primitives, focused tests, browser coverage, and full gates each map to an explicit task.
- Scope: no new route, page, authorization rule, database change, or content redesign is included.
- Type and naming consistency: all route names match the current route registry; all seven chosen icon names exist in the installed Flux icon set; the account current-state list matches the settings routes.
- Secrets: no real credential or operational secret is included.
- Runtime-generated Superdesign IDs are explicitly taken from CLI output and never committed as placeholder values.
