<?php

namespace App\Http\Controllers\WebControllers;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // 🔥 Log the user in
            Auth::login($user);

            // Regenerate session
            $request->session()->regenerate();

            return response()->json([
                'message' => 'User created and logged in successfully'
            ], 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            // Check if user exists
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json([
                    'error' => 'User does not exist.'
                ], 404); // 404 Not Found
            }

            // Attempt to authenticate
            if (!Auth::attempt($credentials, $request->boolean('remember'))) {
                return response()->json([
                    'error' => 'Invalid Password.'
                ], 401); // 401 Unauthorized
            }

            $user = Auth::user();

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        // Logout the user from the session
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function me(Request $request)
    {

        error_log("request arrive");
        $user = $request->user(); // returns authenticated user from session

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json(['user' => $user], 200);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Not authenticated'
            ], 401);
        }

        try {

            $mode = $request->input('mode'); // update | forgot

            if (!in_array($mode, ['update', 'forgot'])) {
                return response()->json([
                    'error' => 'Invalid mode'
                ], 400);
            }

            // --------------------------------
            // 🔹 NORMAL UPDATE (needs current password)
            // --------------------------------
            if ($mode === 'update') {

                $validated = $request->validate([
                    'current_password' => 'required|string',
                    'new_password' => 'required|string|min:6|confirmed',
                ]);

                if (!Hash::check($validated['current_password'], $user->password)) {
                    return response()->json([
                        'error' => 'Current password is incorrect'
                    ], 403);
                }

                $newPassword = $validated['new_password'];
            }

            // --------------------------------
            // 🔹 FORGOT FLOW (no current password)
            // --------------------------------
            if ($mode === 'forgot') {

                // You should ensure OTP was verified before reaching here

                $validated = $request->validate([
                    'new_password' => 'required|string|min:6|confirmed',
                ]);

                $newPassword = $validated['new_password'];
            }

            // --------------------------------
            // 🔹 COMMON PASSWORD UPDATE
            // --------------------------------
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'message' => 'Password updated successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            $user->delete();

            // Log out the user after deleting account
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json(['message' => 'Account deleted successfully'], 200);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
