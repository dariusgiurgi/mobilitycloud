<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\MarketingPageController;
use App\Http\Controllers\ParticipantRegistrationController;
use App\Http\Controllers\PlatformProjectPaymentProofController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectActivityExportController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Middleware\RedirectSuspendedAccount;
use App\Models\User;
use App\Support\AuthSessionHash;
use App\Support\PlatformAudit;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])->group(function (): void {
    Route::get('/', [MarketingPageController::class, 'home'])->name('marketing.home');
    Route::get('/features', [MarketingPageController::class, 'features'])->name('marketing.features');
    Route::get('/pricing', [MarketingPageController::class, 'pricing'])->name('marketing.pricing');
    Route::get('/guide', [MarketingPageController::class, 'guide'])->name('marketing.guide');
    Route::get('/help', [MarketingPageController::class, 'help'])->name('marketing.help');
    Route::get('/resources', [MarketingPageController::class, 'resources'])->name('marketing.resources');
    Route::get('/resources/{slug}', [MarketingPageController::class, 'resource'])->name('marketing.resource');
    Route::get('/contact', [MarketingPageController::class, 'contact'])->name('marketing.contact');
    Route::get('/legal/terms', [LegalPageController::class, 'terms'])->name('legal.terms');
    Route::get('/legal/privacy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
    Route::get('/legal/cookies', [LegalPageController::class, 'cookies'])->name('legal.cookies');
    Route::get('/legal/security', [LegalPageController::class, 'security'])->name('legal.security');
    Route::get('/legal/gdpr', [LegalPageController::class, 'gdpr'])->name('legal.gdpr');
    Route::get('/legal/billing', [LegalPageController::class, 'billing'])->name('legal.billing');
    Route::redirect('/terms', '/legal/terms', 301);
    Route::redirect('/privacy', '/legal/privacy', 301);
    Route::redirect('/cookies', '/legal/cookies', 301);
    Route::redirect('/security', '/legal/security', 301);
    Route::redirect('/gdpr', '/legal/gdpr', 301);
    Route::redirect('/billing', '/legal/billing', 301);
    Route::redirect('/ai-agent', '/legal/ai-agent', 301);
    Route::get('/legal/ai-agent', fn () => view('ai-agent'))->name('legal.ai-agent');
    Route::get('/agent.json', function () {
        $url = rtrim(config('app.url', 'https://mobilitycloud.eu'), '/');

        return response()->json([
            'name' => 'MobilityCloud',
            'url' => $url,
            'type' => 'Erasmus+ project writing and management platform',
            'description' => 'MobilityCloud helps organisations write Erasmus+ applications and manage approved mobility projects through budgets, participants, documents, mobility evidence, dissemination reports, tasks and final reporting workflows.',
            'primary_language' => 'en',
            'contact' => 'contact@mobilitycloud.eu',
            'operator' => [
                'name' => 'XEOTYPE SRL',
                'website' => 'https://xeotype.com',
            ],
            'public_pages' => [
                $url.'/',
                $url.'/features',
                $url.'/pricing',
                $url.'/demo',
                $url.'/guide',
                $url.'/help',
                $url.'/resources',
                $url.'/resources/erasmus-project-management-platform',
                $url.'/resources/erasmus-mobility-documents',
                $url.'/resources/erasmus-final-report-evidence',
                $url.'/resources/erasmus-budget-tracking',
                $url.'/resources/mobilitycloud-partner-sharing-kit',
                $url.'/contact',
                $url.'/legal/ai-agent',
                $url.'/legal/gdpr',
                $url.'/legal/billing',
                $url.'/llms.txt',
                $url.'/sitemap.xml',
            ],
            'key_capabilities' => [
                'Erasmus+ application writing',
                'Approved project activation based on declared grant value',
                'Budget baskets and expense evidence',
                'Participant records and participant intake links',
                'Mobility evidence organised by day',
                'Dissemination reports, materials and outputs',
                'Project documents and generated records',
                'Tasks, readiness signals and project collaboration',
            ],
        ]);
    })->name('agent.json');
});
Route::get('/participant-registration/{token}', [ParticipantRegistrationController::class, 'show'])->name('public.participant-registration.show');
Route::post('/participant-registration/{token}', [ParticipantRegistrationController::class, 'store'])->name('public.participant-registration.store');

Route::get('/account-suspended', function () {
    return view('account-suspended');
})->name('account.suspended');

Route::match(['GET', 'POST'], '/account-suspended/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('filament.admin.auth.login');
})->name('account.suspended.logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', function (Request $request) {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return redirect()->intended(Dashboard::getUrl(panel: $user instanceof User && $user->isPlatformAdmin() ? 'platform' : 'admin'));
        }

        return view('auth.verify-email', [
            'email' => $user?->email,
        ]);
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        $user = $request->user();

        return redirect()->intended(Dashboard::getUrl(panel: $user instanceof User && $user->isPlatformAdmin() ? 'platform' : 'admin'))
            ->with('status', 'Your email address has been verified.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::match(['GET', 'POST'], '/email/verify/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('filament.admin.auth.login');
    })->name('verification.logout');
});

Route::middleware(['auth', RedirectSuspendedAccount::class])->get('/app/onboarding', function (Request $request) {
    $user = $request->user();

    if ($user instanceof User && $user->isPlatformAdmin()) {
        return redirect()->route('filament.platform.pages.dashboard');
    }

    return redirect()->to(Dashboard::getUrl(panel: 'admin'));
})->name('app.onboarding');

Route::middleware(['auth', RedirectSuspendedAccount::class])->post('/app/organisations', function (Request $request) {
    $user = $request->user();

    if ($user instanceof User && $user->isPlatformAdmin()) {
        return redirect()->route('filament.platform.pages.dashboard');
    }

    $data = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
    ]);

    if (filled($data['name'] ?? null)) {
        $user->update(['name' => trim($data['name'])]);
    }

    return redirect()->to(Dashboard::getUrl(panel: 'admin'));
})->name('app.organisations.store');

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

    PlatformAudit::log('impersonation.started', 'Started impersonating '.$user->email, $user, [
        'impersonator_id' => $impersonator->id,
        'target_user_id' => $user->id,
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

    return redirect()->to(Dashboard::getUrl(panel: 'admin'));
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

Route::get('/project-invitations/{token}', [ProjectInvitationController::class, 'accept'])
    ->name('project-invitations.accept');

Route::middleware(['auth', RedirectSuspendedAccount::class])->group(function () {
    Route::get('/platform/project-payments/{project}/approval-proof', PlatformProjectPaymentProofController::class)
        ->name('platform.project-payments.approval-proof');
    Route::get('/projects/{project}/export', [ProjectExportController::class, 'report'])->name('projects.export');
    Route::get('/projects/{project}/activity-export', ProjectActivityExportController::class)->name('projects.activity.export');
    Route::get('/projects/{project}/export-application', [ProjectExportController::class, 'exportApplication'])->name('projects.export-application');
    Route::get('/projects/{project}/export-application-word', [ProjectExportController::class, 'exportApplicationWord'])->name('projects.export-application-word');
    Route::get('/projects/{project}/export-application-pack', [ProjectExportController::class, 'exportApplicationPack'])->name('projects.export-application-pack');
    Route::get('/projects/{project}/final-archive', [ProjectExportController::class, 'finalArchive'])->name('projects.final-archive');
    Route::get('/projects/{project}/export-participants', [ProjectExportController::class, 'participantsCsv'])->name('projects.export-participants');
    Route::get('/projects/{project}/participant-import-template', [ProjectExportController::class, 'participantImportTemplate'])->name('projects.participant-import-template');
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
