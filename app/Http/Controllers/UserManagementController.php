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
                'username' => 'required|string|max:255',
                'password' => 'required|string|min:8|confirmed', // password_confirmation required
                'role' => 'required|string|in:admin,staff',
            ]);

            $ExistingUsername = User::where('username', $validated['username'])->first();

            if ($ExistingUsername && $ExistingUsername->isActive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username already taken.'
                ], 409);
            }

            // Check if user exists by fname + lname
            $user = User::where('fname', $validated['fname'])
                        ->where('lname', $validated['lname'])
                        ->first();

            // If exists and not active, reactivate and update credentials
            if ($user && !$user->isActive) {
                $user->update([
                    'username' => $validated['username'],
                    'password' => Hash::make($validated['password']),
                    'role' => $validated['role'], 
                    'isActive' => true
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'User added successfully',
                    'user' => [
                        'id' => $user->id,
                        'fname' => $user->fname,
                        'lname' => $user->lname,
                        'username' => $user->username,
                        'role' => $user->role,
                    ]
                ]);
            }

            // If exists and active, reject
            if ($user && $user->isActive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already exists.'
                ], 409);
            }

            // Create new user
            $user = User::create([
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'isActive' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($user->role) . ' user created successfully.',
                'user' => [
                    'id' => $user->id,
                    'fname' => $user->fname,
                    'lname' => $user->lname,
                    'username' => $user->username,
                    'role' => $user->role,
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
    
    public function update(Request $request, $id) 
    {
        try {
            $validated = $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'password' => 'nullable|string|min:8|confirmed', // password_confirmation required
                'role' => 'required|string|in:admin,staff',
            ]);

            $user = User::findOrFail($id);

            $existingUserWithUsername = User::where('username', $validated['username'])
                                            ->where('id', '!=', $id)
                                            ->where('isActive', true)
                                            ->first();

            if ($existingUserWithUsername) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username already taken by another user.',
                    'errors' => [
                        'username' => ['This username is already taken.']
                    ]
                ], 422);
            }

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']); // Don't update password
            }

            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'fname' => $user->fname,
                    'lname' => $user->lname,
                    'username' => $user->username,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating user: ' . $e->getMessage(), [
                'user_id' => $id,
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the user.'
            ], 500);
        }
    }

    
     public function disable(User $user)
    {
        try {
            // Already inactive
            if (!$user->isActive) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User is already inactive.'
                ], 400);
            }

            // Count total active users
            $activeUserCount = User::where('isActive', true)->count();

            // Count total active admin users
            $activeAdminCount = User::where('isActive', true)
                                    ->where('role', 'admin')
                                    ->count();

            // Prevent disabling the last active user
            if ($activeUserCount === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot disable the last active user.'
                ], 403);
            }

            // Prevent disabling the last active admin
            if ($user->role === 'admin' && $activeAdminCount === 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot disable the last active admin.'
                ], 403);
            }

            // Properly update isActive to boolean false
            $user->isActive = false;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'User disabled successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error disabling user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error disabling user.'
            ], 500);
        }
    }
}
