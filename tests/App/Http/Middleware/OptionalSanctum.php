<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctum
{
    const string ERROR_MESSAGE = 'Unauthorized';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // Only if there is a bearer token, authenticate the user
        if ($request->bearerToken()) {
            $user = auth()->guard($guard)->user();

            if (! $user) {
                return response()->json([
                    'message' => static::ERROR_MESSAGE,
                ], Response::HTTP_UNAUTHORIZED);
            }

            /** @psalm-suppress UndefinedInterfaceMethod */
            auth()->guard($guard)->setUser($user);
        }

        return $next($request);
    }
}
