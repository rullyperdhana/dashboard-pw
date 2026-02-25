<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Generate Math Captcha
     */
    public function getCaptcha()
    {
        $a = rand(1, 10);
        $b = rand(1, 10);
        $op = rand(0, 1) ? '+' : '-';
        if ($op === '-' && $a < $b) {
            $temp = $a;
            $a = $b;
            $b = $temp;
        }

        $question = "$a $op $b = ?";
        $answer = $op === '+' ? ($a + $b) : ($a - $b);

        $key = 'captcha_' . Str::random(32);
        Cache::put($key, $answer, now()->addMinutes(5));

        return response()->json([
            'success' => true,
            'data' => [
                'captcha_key' => $key,
                'captcha_question' => $question
            ]
        ]);
    }

    /**
     * Login user dan generate token
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha_key' => 'required|string',
            'captcha_answer' => 'required|string',
        ]);

        // 1. Rate Limiting Check
        $throttleKey = Str::lower($request->username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Silakan coba lagi dalam $seconds detik.",
            ], 429);
        }

        // 2. Captcha Verification
        $savedAnswer = Cache::get($request->captcha_key);
        if ($savedAnswer === null || (int) $request->captcha_answer !== (int) $savedAnswer) {
            RateLimiter::hit($throttleKey, 60); // Record failed attempt
            return response()->json([
                'success' => false,
                'message' => 'Jawaban captcha salah atau sudah kedaluwarsa.',
                'captcha_error' => true
            ], 422);
        }

        Cache::forget($request->captcha_key);

        // 3. Authenticate
        $user = User::where('name', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        if ($user->status !== 'approved') {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda belum disetujui atau tidak aktif.'],
            ]);
        }

        // Revoke token lama
        $user->tokens()->delete();

        // Generate token baru
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'skpd_id' => $user->institution,
                    'skpd' => $user->skpd,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get authenticated user info
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load(['skpd', 'employee']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
        ]);
    }
}
