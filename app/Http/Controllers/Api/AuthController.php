<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * REGISTER
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->wallets()->create([
            'name' => 'Dompet Utama',
            'type' => 'cash',
            'balance' => 0,
        ]);

        $token = $user->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Register success',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah'],
            ]);
        }

        $token = $user->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * GOOGLE LOGIN
     */
    public function googleLogin(Request $request)
    {
        $validated = $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $validated['id_token'];

        // Verifikasi token ke Google API
        $response = \Illuminate\Support\Facades\Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$idToken}");

        if ($response->failed() || isset($response['error'])) {
            return response()->json([
                'message' => 'Token Google tidak valid atau kedaluwarsa',
                'error' => $response->json() ?? 'Gagal menghubungi server Google',
            ], 400);
        }

        $payload = $response->json();

        // Ambil data dari payload Google
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? 'User';
        $avatar = $payload['picture'] ?? null;
        $providerId = $payload['sub'] ?? null; // ID Unik User Google

        if (!$email || !$providerId) {
            return response()->json([
                'message' => 'Gagal mengambil email atau provider ID dari akun Google'
            ], 400);
        }

        // Cari user berdasarkan provider_id atau email
        $user = User::where('provider', 'google')
            ->where('provider_id', $providerId)
            ->first();

        if (!$user) {
            // Jika provider_id belum ada, cek apakah ada user dengan email yang sama
            $user = User::where('email', $email)->first();

            if ($user) {
                // Hubungkan user lama dengan Google
                $user->update([
                    'provider' => 'google',
                    'provider_id' => $providerId,
                    'avatar' => $avatar ?? $user->avatar,
                ]);
            } else {
                // Buat user baru
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt(\Illuminate\Support\Str::random(24)),
                    'avatar' => $avatar,
                    'provider' => 'google',
                    'provider_id' => $providerId,
                ]);

                $user->wallets()->create([
                    'name' => 'Dompet Utama',
                    'type' => 'cash',
                    'balance' => 0,
                ]);
            }
        } else {
            // Update foto profil terbaru jika ada perubahan
            if ($avatar && $user->avatar !== $avatar) {
                $user->update(['avatar' => $avatar]);
            }
        }

        // Buat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Google sukses',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * PROFILE
     */
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Logout success'
        ]);
    }
}