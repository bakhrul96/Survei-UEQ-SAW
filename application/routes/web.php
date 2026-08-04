<?php

use App\Domain\Study\PeriodStatus;
use App\Http\Controllers\SurveyEntryController;
use App\Livewire\Admin\StudySettings;
use App\Livewire\Survey\ConsentScreener;
use App\Livewire\Survey\Complete;
use App\Livewire\Survey\UnitChooser;
use App\Livewire\Survey\UeqWizard;
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
Route::get('/s/wong-reang/{period:slug}/units/{unit:code}', UeqWizard::class)
    ->name('survey.wizard');
Route::get('/s/wong-reang/{period:slug}/complete', Complete::class)->name('survey.complete');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('admin/dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/study', StudySettings::class)->name('study-settings');
});

require __DIR__.'/settings.php';
