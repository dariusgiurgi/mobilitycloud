<?php

namespace App\Http\Middleware;

use App\Support\DemoWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectDemoWriteRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->isMethodSafe()
            && ! $request->is('livewire/update')
            && DemoWorkspace::isVisitor(auth('demo')->user())
        ) {
            abort(403, 'The public demo is read-only.');
        }

        return $next($request);
    }
}
