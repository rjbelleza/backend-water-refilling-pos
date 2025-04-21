<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\CategoryController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::apiResource('users', UserManagementController::class);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/category', [CategoryController::class, 'store']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::delete('/delete/category/{category}', [CategoryController::class, 'destroy']);
    
    // Staff routes
    Route::middleware('ability:staff')->group(function () {
        // Staff endpoints here
    });
    
    // Admin routes
    Route::middleware('ability:admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
    });
});
