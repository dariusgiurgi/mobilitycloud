<?php

namespace App\Http\Responses\Filament;

use App\Models\User;
use App\Filament\Pages\Dashboard;
use App\Services\ProjectInvitationNotificationService;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class UnifiedRegistrationResponse implements RegistrationResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = $request->user();

        if ($user instanceof User && $user->is_suspended) {
            return redirect()->route('account.suspended');
        }

        if ($user instanceof User && $user->isPlatformAdmin()) {
            return redirect()->route('filament.platform.pages.dashboard');
        }

        if ($user instanceof User) {
            app(ProjectInvitationNotificationService::class)->syncPendingFor($user);

            return redirect()->to(Dashboard::getUrl(panel: 'admin'));
        }

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
