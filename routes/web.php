<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ProjectExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'report'])->name('projects.export');
    Route::get('/projects/{project}/export-application', [ProjectExportController::class, 'exportApplication'])->name('projects.export-application');
    Route::get('/calc/{type}/export', [ProjectExportController::class, 'calcExport'])->name('calc.export');
    Route::get('/attachments/participants/{attachment}', [AttachmentDownloadController::class, 'participant'])
        ->name('attachments.participants.download');
    Route::get('/attachments/expenses/{expense}', [AttachmentDownloadController::class, 'expense'])
        ->name('attachments.expenses.download');
});
