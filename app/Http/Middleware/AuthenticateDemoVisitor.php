<?php

namespace App\Http\Middleware;

use App\Support\DemoWorkspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDemoVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('demo') && ! $request->is('demo/*') && ! $this->isDemoLivewireRequest($request)) {
            return $next($request);
        }

        $visitor = DemoWorkspace::visitor();

        abort_unless($visitor, 503, 'The live demo is being prepared.');

        // The standard Filament authentication middleware runs before panel
        // middleware, so establish both isolated demo identity and web request
        // identity here. No session login is persisted for either guard.
        Auth::guard('web')->setUser($visitor);
        Auth::guard('demo')->setUser($visitor);

        return $next($request);
    }

    private function isDemoLivewireRequest(Request $request): bool
    {
        if (! $request->is('livewire/update')) {
            return false;
        }

        $snapshot = (string) data_get($request->all(), 'components.0.snapshot', '');

        // Livewire stores the URI in its JSON snapshot. JSON escapes the
        // separator ("demo\\/calendar"), so looking for "demo/" misses
        // every nested demo page and leaves the request unauthenticated.
        return str_contains($snapshot, '"path":"demo');
    }
}
