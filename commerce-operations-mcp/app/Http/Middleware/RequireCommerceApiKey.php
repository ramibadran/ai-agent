<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCommerceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('commerce.api_key');

        if ($configuredKey === '') {
            return responseJson(Response::HTTP_SERVICE_UNAVAILABLE, 'Commerce API key is not configured.');
        }

        if (! hash_equals($configuredKey, (string) $request->header('X-Commerce-Api-Key'))) {
            return responseJson(Response::HTTP_UNAUTHORIZED, 'Unauthorized.');
        }

        return $next($request);
    }
}
