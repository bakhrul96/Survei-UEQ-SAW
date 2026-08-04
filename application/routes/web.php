<?php

use App\Http\Controllers\SurveyEntryController;
use App\Livewire\Admin\StudySettings;
use App\Livewire\Survey\ConsentScreener;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/s/wong-reang/{period:slug}', SurveyEntryController::class)->name('survey.entry');
Route::get('/s/wong-reang/{period:slug}/consent', ConsentScreener::class)->name('survey.consent');
Route::view('/s/wong-reang/{period:slug}/ineligible', 'survey.ineligible')->name('survey.ineligible');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('admin/dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/study', StudySettings::class)->name('study-settings');
});

require __DIR__.'/settings.php';
