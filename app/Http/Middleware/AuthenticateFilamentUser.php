<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Workspace;
use App\Services\AccountWorkspaceService;
use App\Filament\Pages\Dashboard;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
            && Filament::getTenant() instanceof Workspace
        ) {
            $workspace = app(AccountWorkspaceService::class)->ensureFor($user);
            $tenant = Filament::getTenant();

            if ((int) $tenant->getKey() !== (int) $workspace->getKey()) {
                $target = $this->accountTenantUrl($request, $workspace);

                if ($request->fullUrl() !== $target) {
                    throw new HttpResponseException(new RedirectResponse($target));
                }
            }
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

    private function accountTenantUrl($request, Workspace $workspace): string
    {
        $segments = $request->segments();

        if (($segments[0] ?? null) === 'app' && isset($segments[1])) {
            $segments[1] = $workspace->slug;
        }

        $url = url('/'.implode('/', $segments));

        return $request->getQueryString()
            ? $url.'?'.$request->getQueryString()
            : $url;
    }
}
