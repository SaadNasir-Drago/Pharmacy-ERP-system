<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\BudgetController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\SecurityAlertController;
use App\Http\Controllers\API\DashboardController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Dashboard Module (System Overview)
    Route::get('/dashboard', [DashboardController::class, 'getSystemOverview']);

    // Sales Module (Orders)
    Route::middleware('role:admin,sales')->group(function () {
        Route::apiResource('orders', OrderController::class);
    });

    // Inventory Module (Products)
    Route::middleware('role:admin,inventory')->group(function () {
        Route::apiResource('products', ProductController::class);
    });

    // Finance Module (Budgets)
    Route::middleware('role:admin,finance')->group(function () {
        Route::apiResource('budgets', BudgetController::class);
    });

    // HR Module (Employees)
    Route::middleware('role:admin,hr')->group(function () {
        Route::apiResource('employees', EmployeeController::class);
    });

    // IT Module (Security Alerts)
    Route::middleware('role:admin,it')->group(function () {
        Route::apiResource('security-alerts', SecurityAlertController::class);
    });
});