<?php

namespace App\Http\Responses\Filament;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class UnifiedLogoutResponse implements LogoutResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->route('filament.admin.auth.login');
    }
}
