<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PinAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [PinAuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [PinAuthController::class, 'logout']);
    Route::get('/user', [PinAuthController::class, 'user']);
    
    // Staff routes
    Route::middleware('ability:staff')->group(function () {
        // Staff endpoints here
    });
    
    // Admin routes
    Route::middleware('ability:admin')->group(function () {
        // Admin endpoints, including PIN management
    });
});
