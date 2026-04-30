<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

/**
 * Middleware that ensures an incoming request carries a valid JWT token.
 *
 * Returns structured JSON errors instead of redirecting, which is appropriate
 * for a stateless API.
 */
class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException) {
            return $this->unauthorizedResponse('Token has expired.');
        } catch (TokenInvalidException) {
            return $this->unauthorizedResponse('Token is invalid.');
        } catch (JWTException) {
            return $this->unauthorizedResponse('Token not provided.');
        }

        return $next($request);
    }

    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => [],
        ], 401);
    }
}
