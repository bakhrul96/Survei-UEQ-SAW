<?php

use App\Livewire\Admin\StudySettings;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('admin/dashboard', 'dashboard')->name('dashboard');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/study', StudySettings::class)->name('study-settings');
});

require __DIR__.'/settings.php';
