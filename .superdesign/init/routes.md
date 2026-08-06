# Routes and navigation destinations

All destinations below are implemented and protected by the existing application middleware.

| Group | Label | Route name | Path | Current-state rule |
| --- | --- | --- | --- | --- |
| Ikhtisar | Dashboard | `admin.dashboard` | `/admin/dashboard` | `admin.dashboard` |
| Pengumpulan Data | Pengaturan Studi | `admin.study-settings` | `/admin/study` | `admin.study-settings` |
| Pengumpulan Data | Respons | `admin.responses` | `/admin/responses` | `admin.responses` |
| Pengumpulan Data | Laporan & Ekspor | `admin.reports` | `/admin/reports` | `admin.reports` |
| Analisis | Perhitungan | `admin.calculations` | `/admin/calculations` | `admin.calculations` |
| Analisis | Penilaian Teknis | `admin.technical-assessments` | `/admin/technical-assessments` | `admin.technical-assessments` |
| Akun | Pengaturan Akun | `profile.edit` | `/settings/profile` | `profile.edit`, `security.edit`, or `appearance.edit` |

Related settings routes:

- `security.edit` -> `/settings/security`
- `appearance.edit` -> `/settings/appearance`

Survey respondent routes under `/s/wong-reang/{period}` are intentionally excluded from the administrator sidebar because they are part of the public respondent flow.

CSV/XLSX routes are actions exposed inside Dashboard and Reports rather than standalone navigation pages.

## Source: `application/routes/web.php`

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Http\Controllers\Admin\AggregateReportExportController;
use App\Http\Controllers\Admin\RawSurveyExportController;
use App\Http\Controllers\SurveyEntryController;
use App\Livewire\Admin\Calculations;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\Responses;
use App\Livewire\Admin\StudySettings;
use App\Livewire\Admin\TechnicalAssessments;
use App\Livewire\Survey\Complete;
use App\Livewire\Survey\ConsentScreener;
use App\Livewire\Survey\UeqWizard;
use App\Livewire\Survey\UnitChooser;
use App\Models\EvaluationPeriod;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/s/wong-reang/{period:slug}', SurveyEntryController::class)->name('survey.entry');
Route::get('/s/wong-reang/{period:slug}/consent', ConsentScreener::class)->name('survey.consent');
Route::get('/s/wong-reang/{period:slug}/ineligible', function (EvaluationPeriod $period) {
    abort_unless($period->status === PeriodStatus::Active, 404);

    return view('survey.ineligible');
})->name('survey.ineligible');
Route::get('/s/wong-reang/{period:slug}/units', UnitChooser::class)
    ->name('survey.units');
Route::get('/s/wong-reang/{period:slug}/units/{unit}', UeqWizard::class)
    ->withoutScopedBindings()
    ->name('survey.wizard');
Route::get('/s/wong-reang/{period:slug}/complete', Complete::class)->name('survey.complete');

Route::middleware(['auth', 'verified', 'admin.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/responses', Responses::class)->name('responses');
    Route::get('/periods/{period}/exports/raw.csv', [RawSurveyExportController::class, 'csv'])->name('exports.raw.csv');
    Route::get('/periods/{period}/exports/raw.xlsx', [RawSurveyExportController::class, 'xlsx'])->name('exports.raw.xlsx');
    Route::get('/periods/{period}/exports/aggregate.csv', [AggregateReportExportController::class, 'csv'])->name('exports.aggregate.csv');
    Route::get('/periods/{period}/exports/aggregate.xlsx', [AggregateReportExportController::class, 'xlsx'])->name('exports.aggregate.xlsx');
    Route::get('/study', StudySettings::class)->name('study-settings');
    Route::get('/calculations', Calculations::class)->name('calculations');
    Route::get('/technical-assessments', TechnicalAssessments::class)->name('technical-assessments');
    Route::get('/reports', Reports::class)->name('reports');
});

require __DIR__.'/settings.php';
```

## Source: `application/routes/settings.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
```
