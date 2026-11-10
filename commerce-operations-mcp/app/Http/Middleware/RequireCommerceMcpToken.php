<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCommerceMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('commerce.mcp_token');

        if ($configuredToken === '') {
            return response()->json([
                'error' => 'Commerce MCP token is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (! hash_equals($configuredToken, (string) $request->bearerToken())) {
            return response()->json([
                'error' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        return $next($request);
    }
}
