<?php

namespace App\Http\Responses\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Services\ProjectInvitationNotificationService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class UnifiedLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        if ($user instanceof User && $user->is_suspended) {
            return redirect()->route('account.suspended');
        }

        if ($user instanceof User) {
            app(ProjectInvitationNotificationService::class)->syncPendingFor($user);

            if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
                if ($this->intendedUrlIsEmailVerification($request)) {
                    return redirect()->intended(route('verification.notice'));
                }

                $user->sendEmailVerificationNotification();

                return redirect()->route('verification.notice')
                    ->with('status', 'verification-link-sent');
            }

            if ($user->isPlatformAdmin()) {
                return redirect()->route('filament.platform.pages.dashboard');
            }

            return redirect()->to(Dashboard::getUrl(panel: 'admin'));
        }

        return redirect()->route('filament.admin.pages.dashboard');
    }

    private function intendedUrlIsEmailVerification($request): bool
    {
        $intended = (string) $request->session()->get('url.intended', '');

        if ($intended === '') {
            return false;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        return is_string($path)
            && (
                str_contains($path, '/email/verify/')
                || str_contains($path, '/app/email-verification/verify/')
                || str_contains($path, '/platform/email-verification/verify/')
            );
    }
}
