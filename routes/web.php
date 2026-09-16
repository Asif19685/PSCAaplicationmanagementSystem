<?php

use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Live Monitoring
    Route::get('/dashboard', [MonitoringController::class, 'dashboard'])->name('dashboard');

    // Monitoring Actions & Logs
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::post('/test/{website}', [MonitoringController::class, 'testSingle'])->name('test');
        Route::post('/batch/run', [MonitoringController::class, 'runBatch'])->name('batch.run');
        Route::get('/logs', [MonitoringController::class, 'logs'])->name('logs');
        Route::get('/logs/{log}/modal', [MonitoringController::class, 'getLogModal'])->name('log.modal');
        Route::get('/batches', [MonitoringController::class, 'batches'])->name('batches');
        Route::get('/batches/{batch:batch_id}', [MonitoringController::class, 'batchDetail'])->name('batch.detail');
    });

    // Dynamic Website Management (CRUD)
    Route::resource('websites', WebsiteController::class);
    Route::patch('/websites/{website}/toggle', [WebsiteController::class, 'toggleStatus'])->name('websites.toggle');

    // Reporting Engine
    Route::resource('reports', ReportController::class);
    Route::get('/reports/{report}/print', [ReportController::class, 'print'])->name('reports.print');

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
