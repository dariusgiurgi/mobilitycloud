<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectExportController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'report'])->name('projects.export');
    Route::get('/projects/{project}/export-application', [ProjectExportController::class, 'exportApplication'])->name('projects.export-application');
    Route::get('/calc/{type}/export', [ProjectExportController::class, 'calcExport'])->name('calc.export');
});
