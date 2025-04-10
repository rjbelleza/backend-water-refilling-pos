<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'ability:admin']);
    }
    
    public function index()
    {
        $users = User::select('id', 'name', 'username', 'role', 'created_at')->get();
        
        return response()->json(['users' => $users]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,staff',
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);
        
        return response()->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ]
        ], 201);
    }
    
    public function show(User $user)
    {
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]
        ]);
    }
    
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|string|in:admin,staff',
        ]);
        
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        
        $user->update($validated);
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ]
        ]);
    }
    
    public function destroy(User $user)
    {
        $user->tokens()->delete(); // Delete all user tokens
        $user->delete();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
