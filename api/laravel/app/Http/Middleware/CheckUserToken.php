<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckUserToken
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('user')->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        return $next($request);
    }
}
