<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::where('pin', $request->pin)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'pin' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token with abilities based on role
        if ($user->role === 'admin') {
            $token = $user->createToken($request->device_name, ['admin', 'staff'])->plainTextToken;
        } else {
            $token = $user->createToken($request->device_name, ['staff'])->plainTextToken;
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'pin' => $user->pin,
                'role' => $user->role,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }
    
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
