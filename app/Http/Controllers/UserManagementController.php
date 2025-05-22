<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'ability:admin']);
    }
    
    public function index()
    {
        try {
            $users = User::select('id', 'fname', 'lname', 'username', 'role', 'created_at')->where('isActive', 1)->get();
        
            return response()->json($users);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users,username',
                'password' => 'required|string|min:8|confirmed', 
                'role' => 'required|string|in:admin,staff',
            ]);

            $user = User::where('fname', $validated['fname'])
                        ->where('lname', $validated['lname'])
                        ->first();

            if ($user && !$user->isActive) {
                User::update([
                    'username' => $validated['username'],
                    'password' => $validated['password'],
                    'isActive' => true
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'User added successfully',
                    'product' => $user
                ]);
            }

            if ($user && $user->isActive) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'User already exists.'
                ], 409);
            }

            // Create the user with hashed password
            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'isActive' => true
            ]);

            return response()->json([
                'message' => ucfirst($user->role) . ' user created successfully.',
                'user' => [
                    'id' => $user->id,
                    'fname' => $user->name,
                    'lname' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating user' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
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
