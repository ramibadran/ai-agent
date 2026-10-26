<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (JWTAuth::getToken() === null) {
                return $this->unauthorized('Authorization header is missing.');
            }

            $user = JWTAuth::parseToken()->authenticate();

            if ($user === null) {
                return $this->unauthorized('The authenticated user was not found.');
            }

            $request->attributes->set('auth_user', $user);
            auth()->setUser($user);
        } catch (TokenExpiredException) {
            return $this->unauthorized('Token has expired.');
        } catch (TokenInvalidException|JWTException) {
            return $this->unauthorized('Token is invalid.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return responseJson(Response::HTTP_UNAUTHORIZED, $message);
    }
}
