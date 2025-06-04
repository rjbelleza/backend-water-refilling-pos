<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StockSettingController;


// Public routes
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
    Route::apiResource('users', UserManagementController::class);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('/category', [CategoryController::class, 'store']);
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::post('/product', [ProductController::class, 'store']);
    Route::get('/products', [ProductController::class, 'index']);
    
    Route::put('/product/{id}', [ProductController::class, 'updateDetails']);
    Route::patch('/product/delete/{product}', [ProductController::class, 'disable']);
    Route::put('/product/{product}/update-stock', [ProductController::class, 'updateStock']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    
    // Admin routes
    Route::middleware('ability:admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);

        Route::post('/expense', [ExpenseController::class, 'store']);
        Route::get('/expenses', [ExpenseController::class, 'index']);

        Route::get('/profit', [ProfitController::class, 'getMonthlyReport']);

        Route::get('/dashboard-summary', [DashboardController::class, 'getSummary']);
        Route::get('/dashboard-graph', [DashboardController::class, 'getGraphData']);

        Route::post('/user/add', [UserManagementController::class, 'store']);
        Route::put('/user/update/{id}', [UserManagementController::class, 'update']);
        Route::put('/user/{user}/disable', [UserManagementController::class, 'disable']);

        Route::put('/low_stock/update', [StockSettingController::class, 'updateThreshold']);
        Route::get('/alert/low_stock_products', [StockSettingController::class, 'lowStockProducts']);
    });
});
