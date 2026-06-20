<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\ProjectDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'report'])->name('projects.export');
    Route::get('/projects/{project}/export-application', [ProjectExportController::class, 'exportApplication'])->name('projects.export-application');
    Route::get('/projects/{project}/export-participants', [ProjectExportController::class, 'participantsCsv'])->name('projects.export-participants');
    Route::get('/calc/{type}/export', [ProjectExportController::class, 'calcExport'])->name('calc.export');
    Route::get('/attachments/participants/{attachment}', [AttachmentDownloadController::class, 'participant'])
        ->name('attachments.participants.download');
    Route::get('/attachments/expenses/{expense}', [AttachmentDownloadController::class, 'expense'])
        ->name('attachments.expenses.download');
    Route::get('/projects/{project}/documents/{document}/attendance', [ProjectDocumentController::class, 'attendance'])
        ->name('project-documents.attendance');
    Route::get('/projects/{project}/documents/{document}/signed', [ProjectDocumentController::class, 'signed'])
        ->name('project-documents.signed');
    Route::get('/projects/{project}/documents/{document}/file', [ProjectDocumentController::class, 'file'])
        ->name('project-documents.file');
});
