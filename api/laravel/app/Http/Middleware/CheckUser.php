<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
class CheckUser
{
    public function handle($request, Closure $next)
    {
        Auth::shouldUse('user');
        if (!Auth::guard('user')->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        return $next($request);
    }
}
