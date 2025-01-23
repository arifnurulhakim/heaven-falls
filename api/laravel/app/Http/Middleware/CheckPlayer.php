<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPlayer
{
    public function handle($request, Closure $next)
    {
        Auth::shouldUse('player');
        if (!Auth::guard('player')->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        return $next($request);
    }
}

