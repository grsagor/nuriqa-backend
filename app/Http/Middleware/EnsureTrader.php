<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

/** Only Trader accounts can use the trader verification endpoints. */
class EnsureTrader
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! $user?->isTrader()) {
            return response()->json(['success' => false, 'message' => 'Only trader accounts have a verification application.'], 403);
        }

        return $next($request);
    }
}
