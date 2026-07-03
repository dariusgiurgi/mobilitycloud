<?php

use App\Http\Controllers\AttachmentDownloadController;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Middleware\RedirectSuspendedAccount;
use App\Http\Controllers\WorkspaceBackupController;
use App\Http\Controllers\WorkspaceInvitationController;
use App\Http\Controllers\WorkspaceReportController;
use App\Support\AuthSessionHash;
use App\Models\User;
use App\Support\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app/login');
});

Route::get('/account-suspended', function () {
    return view('account-suspended');
})->name('account.suspended');

Route::match(['GET', 'POST'], '/account-suspended/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('filament.admin.auth.login');
})->name('account.suspended.logout');

Route::get('/platform/impersonation/{user}/start', function (Request $request, User $user) {
    $impersonator = $request->user();

    if (! $impersonator) {
        return redirect()->route('filament.admin.auth.login');
    }

    abort_unless($impersonator->isPlatformAdmin(), 403);
    abort_if($user->isPlatformAdmin() || $user->is_suspended || $user->is($impersonator), 403);

    $reason = trim((string) $request->session()->pull('impersonation_reason_'.$user->id, ''));

    if ($reason === '') {
        return redirect()
            ->to(PlatformUserResource::getUrl(panel: 'platform'))
            ->with('error', 'Impersonation requires a reason.');
    }

    $workspace = $user->currentWorkspace ?: $user->workspaces()->orderBy('name')->first();

    PlatformAudit::log('impersonation.started', 'Started impersonating '.$user->email, $user, [
        'impersonator_id' => $impersonator->id,
        'target_user_id' => $user->id,
        'workspace_id' => $workspace?->id,
        'reason' => $reason,
    ]);

    Auth::guard('web')->login($user);
    $request->session()->regenerate();
    AuthSessionHash::sync($request, $user);
    $request->session()->put([
        'impersonator_id' => $impersonator->id,
        'impersonated_user_id' => $user->id,
        'impersonation_started_at' => now()->toISOString(),
        'impersonation_reason' => $reason,
    ]);

    if (! $workspace) {
        return redirect()->route('filament.admin.tenant');
    }

    return redirect()->to(Dashboard::getUrl(panel: 'admin', tenant: $workspace));
})->name('platform.impersonation.start');

Route::get('/impersonation/stop', function (Request $request) {
    $impersonatorId = $request->session()->get('impersonator_id');
    $target = $request->user();

    if (! $impersonatorId) {
        return redirect()->route('filament.admin.auth.login');
    }

    $impersonator = User::find($impersonatorId);

    $request->session()->forget([
        'impersonator_id',
        'impersonated_user_id',
        'impersonation_started_at',
    ]);

    if (! $impersonator) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    }

    Auth::guard('web')->login($impersonator);
    AuthSessionHash::sync($request, $impersonator);

    if ($target) {
        PlatformAudit::log('impersonation.ended', 'Stopped impersonating '.$target->email, $target, [
            'impersonator_id' => $impersonator->id,
            'target_user_id' => $target->id,
            'reason' => $request->session()->pull('impersonation_reason'),
        ]);
    }

    return redirect()->route('filament.platform.pages.dashboard');
})->name('platform.impersonation.stop');

Route::middleware(['auth', RedirectSuspendedAccount::class])->group(function () {
    Route::get('/workspace-invitations/{token}', [WorkspaceInvitationController::class, 'accept'])
        ->name('workspace-invitations.accept');
    Route::get('/workspaces/{workspace}/backup', [WorkspaceBackupController::class, 'download'])
        ->name('workspaces.backup');
    Route::get('/workspaces/{workspace}/report.csv', [WorkspaceReportController::class, 'csv'])
        ->name('workspaces.report.csv');
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'report'])->name('projects.export');
    Route::get('/projects/{project}/export-application', [ProjectExportController::class, 'exportApplication'])->name('projects.export-application');
    Route::get('/projects/{project}/export-application-word', [ProjectExportController::class, 'exportApplicationWord'])->name('projects.export-application-word');
    Route::get('/projects/{project}/export-application-pack', [ProjectExportController::class, 'exportApplicationPack'])->name('projects.export-application-pack');
    Route::get('/projects/{project}/final-archive', [ProjectExportController::class, 'finalArchive'])->name('projects.final-archive');
    Route::get('/projects/{project}/export-participants', [ProjectExportController::class, 'participantsCsv'])->name('projects.export-participants');
    Route::get('/calc/{type}/export', [ProjectExportController::class, 'calcExport'])->name('calc.export');
    Route::get('/attachments/participants/{attachment}', [AttachmentDownloadController::class, 'participant'])
        ->name('attachments.participants.download');
    Route::get('/attachments/expenses/{expense}', [AttachmentDownloadController::class, 'expense'])
        ->name('attachments.expenses.download');
    Route::get('/projects/{project}/documents/{document}/attendance', [ProjectDocumentController::class, 'attendance'])
        ->name('project-documents.attendance');
    Route::get('/projects/{project}/documents/{document}/expense-report', [ProjectDocumentController::class, 'expenseReport'])
        ->name('project-documents.expense-report');
    Route::get('/projects/{project}/documents/{document}/signed', [ProjectDocumentController::class, 'signed'])
        ->name('project-documents.signed');
    Route::get('/projects/{project}/documents/{document}/file', [ProjectDocumentController::class, 'file'])
        ->name('project-documents.file');
    Route::get('/projects/{project}/expenses/{expense}/civil-convention', [ProjectDocumentController::class, 'civilConvention'])
        ->name('project-documents.civil-convention');
    Route::get('/projects/{project}/expenses/{expense}/payment-statement', [ProjectDocumentController::class, 'paymentStatement'])
        ->name('project-documents.payment-statement');
    Route::get('/projects/{project}/expenses/{expense}/signed/{kind}', [ProjectDocumentController::class, 'signedConvention'])
        ->whereIn('kind', ['agreement', 'payment'])
        ->name('project-documents.convention-signed');
});
