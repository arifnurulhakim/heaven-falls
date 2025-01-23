<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Mail\SendCodeResetPassword;
use App\Models\Player;
use App\Models\ResetCodePassword;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    /**
     * Send a random code to the user's email to initiate the password reset process.
     *
     * @param  ForgotPasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
            ]);
            $email = Player::where('email', $request->email)->first();
            if (!$email) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'email not found',
                    'error_code' => 'EMAIL_NOT_FOUND',
                ], 422);
            }

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors(),
                    'error_code' => 'INPUT_VALIDATION_ERROR',
                ], 422);
            }

            // Delete any existing reset codes for the given email
            ResetCodePassword::where('email', $request->email)->delete();

            // Generate and store a new reset code
            $codeData = ResetCodePassword::create([
                'email' => $request->email,
                'code' => $this->generateRandomCode(),
            ]);

            // Find a player associated with the email
            $player = Player::where('email', $request->email)->first();

            if ($player) {
                // Send reset code email to the player
                Mail::to($request->email)->send(new SendCodeResetPassword([$codeData->code, $player->username]));
                return response()->json([
                    'status' => 'success',
                    'message' => trans('email sent'),
                ], 200);

            } else {
                // If no player is found, check for a user
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    // Send reset code email to the user
                    Mail::to($request->email)->send(new SendCodeResetPassword([$codeData->code, $player->username]));
                    return response()->json([
                        'status' => 'success',
                        'message' => trans('email sent'),
                    ], 200);
                } else {
                    // No user or player found for the email
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email not found.',
                        'error_code' => 'EMAIL_NOT_FOUND',
                    ], 404);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate a random 6-digit code for password reset.
     *
     * @return int
     */
    private function generateRandomCode()
    {
        return mt_rand(100000, 999999);
    }
}
