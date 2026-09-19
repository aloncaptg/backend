<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number ?? '00000000' . rand(100, 999),
            'role' => $request->role ?? 'employee',
            'status' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['phone_number' => 'required|exists:users,phone_number']);
        
        $user = User::where('phone_number', $request->phone_number)->first();
        $otp = rand(100000, 999999);
        
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Mock SMS Send
        return response()->json([
            'message' => 'OTP sent successfully (MOCKED)',
            'mock_otp' => $otp
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|exists:users,phone_number',
            'otp_code' => 'required|string'
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if ($user->otp_code !== $request->otp_code || now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return response()->json(['message' => 'OTP verified']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|exists:users,phone_number',
            'otp_code' => 'required|string',
            'password' => 'required|min:6'
        ]);

        $user = User::where('phone_number', $request->phone_number)->first();

        if ($user->otp_code !== $request->otp_code || now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            // Store the file in public/avatars folder
            $path = $file->store('avatars', 'public');
            
            // Save path to user
            $user->profile_picture = $path;
            $user->save();

            return response()->json([
                'message' => 'Profile picture updated successfully',
                'profile_picture' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['message' => 'No image provided'], 400);
    }
}
