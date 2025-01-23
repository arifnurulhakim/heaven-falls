<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Closure;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        if (!$request->expectsJson()) {
            return route('login');
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
            'error_code' => 'UNAUTHORIZED'
        ], 401);
    }
}
