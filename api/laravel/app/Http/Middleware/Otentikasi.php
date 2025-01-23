<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Otentikasi
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('player')->check()||Auth::guard('user')->user()) {
            return $next($request);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }


    }
}
