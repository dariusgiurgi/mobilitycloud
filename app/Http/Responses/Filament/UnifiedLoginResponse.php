<?php

namespace App\Http\Responses\Filament;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class UnifiedLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        if ($user instanceof User && $user->is_suspended) {
            return redirect()->route('account.suspended');
        }

        if ($user instanceof User && $user->isPlatformAdmin()) {
            return redirect()->route('filament.platform.pages.dashboard');
        }

        return redirect()->route('filament.admin.tenant');
    }
}
