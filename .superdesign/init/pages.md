# Existing pages

## Administrator application

- Dashboard (`admin.dashboard`): research progress, respondent/evaluation totals, per-module status, and quick links.
- Pengaturan Studi (`admin.study-settings`): period configuration, consent text, quality rules, readiness evidence, verification, and activation controls.
- Respons (`admin.responses`): response-quality review, flags, reviewer decisions, and exclusions.
- Laporan & Ekspor (`admin.reports`): aggregate research report, official/preview run metadata, UEQ visualizations, SAW comparison, sensitivity matrix, CSV and XLSX exports.
- Perhitungan (`admin.calculations`): UEQ and SAW calculations, rankings, sensitivity analysis, expert judgment, and operational backlog.
- Penilaian Teknis (`admin.technical-assessments`): technical informants, criteria weights, and consensus.

## Account application

- Profile (`profile.edit`): administrator identity and profile data.
- Security (`security.edit`): password, passkeys, TOTP, and recovery codes.
- Appearance (`appearance.edit`): display preferences.

## Public survey flow

Consent, eligibility, unit selection, UEQ wizard, and completion pages use a separate public survey layout. They should not appear in the authenticated administrator sidebar.

## Current shell problem

The current sidebar exposes only Dashboard and Respons under a generic `Platform` group, while implemented admin pages are discoverable mainly through dashboard buttons. It also contains Repository and Documentation starter-kit links. The redesign must expose all working application pages, organize them by survey workflow, and remove both starter links.

## Dependency trees

All administrator pages follow the same shell path:

```text
named route
└── App\Livewire\Admin\<Component>
    └── resources/views/livewire/admin/<view>.blade.php
        └── layout('layouts.app', title)
            └── resources/views/layouts/app.blade.php
                └── x-layouts::app.sidebar
                    └── resources/views/layouts/app/sidebar.blade.php
```

Concrete mappings:

| Route | Livewire class | View | Layout title |
| --- | --- | --- | --- |
| `admin.dashboard` | `App\Livewire\Admin\Dashboard` | `livewire.admin.dashboard` | Dashboard Studi |
| `admin.study-settings` | `App\Livewire\Admin\StudySettings` | `livewire.admin.study-settings` | Pengaturan Studi |
| `admin.responses` | `App\Livewire\Admin\Responses` | `livewire.admin.responses` | Review Kualitas Respons |
| `admin.reports` | `App\Livewire\Admin\Reports` | `livewire.admin.reports` | Laporan Agregat Penelitian |
| `admin.calculations` | `App\Livewire\Admin\Calculations` | `livewire.admin.calculations` | Kalkulasi UEQ dan SAW |
| `admin.technical-assessments` | `App\Livewire\Admin\TechnicalAssessments` | `livewire.admin.technical-assessments` | Penilaian Informan Teknis |

Settings pages use anonymous Livewire page components and the same global application shell:

```text
profile.edit
└── pages::settings.profile
    └── resources/views/pages/settings/⚡profile.blade.php
        └── x-pages::settings.layout
            └── resources/views/pages/settings/layout.blade.php
                └── layouts.app
                    └── layouts.app.sidebar

security.edit
└── pages::settings.security
    └── resources/views/pages/settings/⚡security.blade.php
        └── x-pages::settings.layout → layouts.app → layouts.app.sidebar

appearance.edit
└── pages::settings.appearance
    └── resources/views/pages/settings/⚡appearance.blade.php
        └── x-pages::settings.layout → layouts.app → layouts.app.sidebar
```

The settings-local `Profile`, `Security`, and `Appearance` navlist remains inside `resources/views/pages/settings/layout.blade.php`; the global sidebar links only to `profile.edit` and marks that item current across all three route names.
