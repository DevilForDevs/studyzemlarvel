<?php

namespace App\Http\Controllers\WebControllers;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;


class VerificationCodeController extends Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:new,old', // new user or old user
        ]);

        $email = $request->email;
        $type = $request->type;

        // If request is for OLD user
        if ($type === 'old') {
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }
        }

        // If request is for NEW user
        if ($type === 'new') {
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                return response()->json([
                    'error' => 'User already exists'
                ], 409);
            }
        }

        // Generate OTP
        $otp = rand(100000, 999999);

        // Store OTP in database (recommended)
        DB::table('otps')->updateOrInsert(
            ['email' => $email],
            [
                'otp' => $otp,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Send email
        Mail::raw("$otp is your OTP", function ($message) use ($email) {
            $message->to($email)->subject('Your OTP Code');
        });

        return response()->json([
            'message' => 'OTP sent successfully'
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $email = $request->email;
        $enteredOtp = $request->otp;

        $otpRecord = DB::table('otps')
            ->where('email', $email)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'error' => 'OTP not found. Please request again.'
            ], 404);
        }

        if (now()->greaterThan($otpRecord->expires_at)) {

            DB::table('otps')->where('email', $email)->delete();

            return response()->json([
                'error' => 'OTP expired'
            ], 400);
        }

        if ($enteredOtp != $otpRecord->otp) {
            return response()->json([
                'error' => 'Invalid OTP'
            ], 400);
        }

        // OTP is valid → delete it
        DB::table('otps')->where('email', $email)->delete();

        // Check if user exists
        $user = User::where('email', $email)->first();

        if ($user) {
            // Login existing user
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'OTP verified and logged in successfully',
                'type' => 'login'
            ], 200);
        }

        // User does not exist → OTP valid but no login
        return response()->json([
            'message' => 'OTP verified successfully',
            'type' => 'signup'
        ], 200);
    }
}
