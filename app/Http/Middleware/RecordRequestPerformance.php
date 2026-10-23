<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordRequestPerformance
{
    public function handle(Request $request, Closure $next)
    {
        $started = microtime(true);
        $response = $next($request);
        if (config('mobilitycloud.performance.enabled')) {
            try {
                Log::channel('performance')->info('http_request', [
                    // Route templates deliberately exclude IDs, tokens, query strings and request bodies.
                    'route' => $request->route()?->uri() ?? 'unmatched',
                    'method' => $request->method(),
                    'status' => $response->getStatusCode(),
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'response_bytes' => strlen((string) $response->getContent()),
                    'peak_memory_bytes' => memory_get_peak_usage(true),
                ]);
            } catch (\Throwable $exception) {
                // Observability must not turn a successful request into an error.
                report($exception);
            }
        }

        return $response;
    }
}
