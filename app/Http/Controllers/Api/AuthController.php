<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('afyasmart')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Account created successfully',
            'token'   => $token,
            'user'    => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials. Please try again.'],
            ]);
        }

        $token = $user->createToken('afyasmart')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user'   => $this->userPayload($request->user()),
        ]);
    }

    // ── Consistent user shape across all endpoints ───────────────
    private function userPayload(User $user): array
    {
        return [
            'id'                      => $user->id,
            'name'                    => $user->name,
            'email'                   => $user->email,
            'phone'                   => $user->phone,
            'is_subscribed'           => $user->is_subscribed,
            'chat_count'              => $user->chat_count,
            'subscription_expires_at' => $user->subscription_expires_at,
        ];
    }
}