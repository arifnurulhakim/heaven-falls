<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use JWTAuth;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    public function auth(Request $request)
    {
        // Memastikan bahwa user terautentikasi melalui JWT token
        // $user = JWTAuth::parseToken()->authenticate();

        // if ($user) {
        //     // Autentikasi berhasil, lanjutkan dengan broadcasting
        //     return Broadcast::auth($request);
        // }

        // // Jika gagal, return 403 Unauthorized
        // return response()->json(['error' => 'Unauthorized'], 403);

        Auth::shouldUse('player');
        if (!Auth::guard('player')->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }else{
            return Broadcast::auth($request);
        }

    }
}