<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPlatformLoginToUnifiedLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('filament.platform.auth.login')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User && $user->is_suspended) {
            return redirect()->route('account.suspended');
        }

        if ($user instanceof User && $user->isPlatformAdmin()) {
            return redirect()->route('filament.platform.pages.dashboard');
        }

        return redirect()->route('filament.admin.auth.login');
    }
}
