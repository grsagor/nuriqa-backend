<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Traders cannot buy or sell until their verification is approved. Everyone else passes straight through.
 * The frontend reads `code` to send the trader to their verification page.
 */
class EnsureCanTrade
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user && ! $user->canTrade()) {
            return response()->json([
                'success' => false,
                'code' => 'trader_verification_required',
                'verification_status' => $user->traderVerificationStatus(),
                'message' => 'Your trader account must be verified before you can buy or sell.',
            ], 403);
        }

        return $next($request);
    }
}
