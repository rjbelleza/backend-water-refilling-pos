<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
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
                'fname' => $user->fname,
                'lname' => $user->lname,
                'username' => $user->username,
                'role' => $user->role,
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:admin,staff',
            'device_name' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'staff',
        ]);

        // Create token with appropriate abilities
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
                'username' => $user->username,
                'role' => $user->role,
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully']);
    }
    
    public function user(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'fname' => $request->user()->fname,
                'lname' => $request->user()->lname,
                'username' => $request->user()->username,
                'role' => $request->user()->role,
            ]
        ]);
    }
}
