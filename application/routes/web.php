<?php

use App\Domain\Study\PeriodStatus;
use App\Http\Controllers\Admin\RawSurveyExportController;
use App\Http\Controllers\SurveyEntryController;
use App\Livewire\Admin\Dashboard;
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
    Route::get('/study', StudySettings::class)->name('study-settings');
    Route::get('/technical-assessments', TechnicalAssessments::class)->name('technical-assessments');
});

require __DIR__.'/settings.php';
