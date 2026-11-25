<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->isResident()) {
            return redirect()->route('resident.dashboard');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isTechnician()) {
            return redirect()->route('technician.dashboard');
        }

        abort(403);
    })->name('dashboard');

    // Resident routes
    Route::middleware('role:resident')->prefix('resident')->name('resident.')->group(function () {
        Route::livewire('/dashboard', 'pages::resident.dashboard')->name('dashboard');

        Route::livewire('/requests/create', 'pages::resident.create-request')->name('requests.create');
        Route::livewire('/requests/{request}', 'pages::resident.request-details')->name('requests.show');

        Route::livewire('/requests/{request}/edit', 'pages::resident.edit-request')
            ->name('requests.edit');
    });

    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');
        Route::livewire('/requests/{maintenanceRequest}', 'pages::admin.request-details')->name('requests.show');
    });

    // Technician routes
    Route::middleware('role:technician')->prefix('technician')->name('technician.')->group(function () {
        Route::livewire('/dashboard', 'pages::technician.dashboard')->name('dashboard');
        Route::livewire('/requests/{request}', 'pages::technician.request-details')->name('requests.show');
    });
});

require __DIR__.'/settings.php';
