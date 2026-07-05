<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AccountWorkspaceService;
use App\Filament\Pages\Dashboard;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

class AuthenticateFilamentUser extends Authenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        if ($user instanceof User && $user->is_suspended) {
            throw new HttpResponseException(redirect()->route('account.suspended'));
        }

        $panel = Filament::getCurrentOrDefaultPanel();

        if (
            $user instanceof User
            && $user->isPlatformAdmin()
            && $panel->getId() === 'admin'
            && ! $request->routeIs('filament.admin.auth.logout')
        ) {
            throw new HttpResponseException(redirect()->route('filament.platform.pages.dashboard'));
        }

        if (
            $user instanceof User
            && ! $user->isPlatformAdmin()
            && $panel->getId() === 'admin'
            && $request->routeIs('filament.admin.tenant')
            && ! $user->hasAnyWorkspaceAccess()
        ) {
            $workspace = app(AccountWorkspaceService::class)->ensureFor($user);

            throw new HttpResponseException(redirect()->to(Dashboard::getUrl(panel: 'admin', tenant: $workspace)));
        }

        abort_if(
            $user instanceof FilamentUser ?
                (! $user->canAccessPanel($panel)) :
                (config('app.env') !== 'local'),
            403,
        );
    }
}
