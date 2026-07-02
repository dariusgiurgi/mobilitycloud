<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectSuspendedAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->is_suspended) {
            return $next($request);
        }

        if ($this->isAllowedSuspendedAccountRoute($request)) {
            return $next($request);
        }

        return redirect()->route('account.suspended');
    }

    private function isAllowedSuspendedAccountRoute(Request $request): bool
    {
        return $request->routeIs('account.suspended')
            || $request->is('account-suspended')
            || $request->is('app/logout')
            || $request->is('platform/logout');
    }
}
