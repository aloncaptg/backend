<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\ExcelController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Inventory Items CRUD
    Route::get('/items', [InventoryController::class, 'getItems']);
    
    Route::middleware('role:admin,chef')->group(function() {
        Route::post('/items', [InventoryController::class, 'storeItem']);
        Route::put('/items/{id}', [InventoryController::class, 'updateItem']);
        Route::delete('/items/{id}', [InventoryController::class, 'destroyItem']);
    });

    // Orders & KDS
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Deliveries (Driver / Courier / Admin)
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/my', [DeliveryController::class, 'myDeliveries']);
    Route::post('/deliveries/{id}/assign', [DeliveryController::class, 'assign']);
    Route::post('/deliveries/{id}', [DeliveryController::class, 'updateDelivery']); // POST for file upload

    // V4 Features
    Route::get('/v4/wastes', [\App\Http\Controllers\Api\V4Controller::class, 'getWastes']);
    Route::post('/v4/wastes', [\App\Http\Controllers\Api\V4Controller::class, 'storeWaste']);
    
    Route::get('/v4/suppliers', [\App\Http\Controllers\Api\V4Controller::class, 'getSuppliers']);
    Route::post('/v4/suppliers', [\App\Http\Controllers\Api\V4Controller::class, 'storeSupplier']);
    
    Route::get('/v4/tasks', [\App\Http\Controllers\Api\V4Controller::class, 'getTasks']);
    Route::post('/v4/tasks', [\App\Http\Controllers\Api\V4Controller::class, 'storeTask']);
    Route::put('/v4/tasks/{id}', [\App\Http\Controllers\Api\V4Controller::class, 'updateTask']);
    Route::post('/v4/tasks/{id}/complete', [\App\Http\Controllers\Api\V4Controller::class, 'completeTask']);
    
    Route::get('/v4/leaves', [\App\Http\Controllers\Api\V4Controller::class, 'getLeaves']);
    Route::post('/v4/leaves', [\App\Http\Controllers\Api\V4Controller::class, 'storeLeave']);
    Route::put('/v4/leaves/{id}/status', [\App\Http\Controllers\Api\V4Controller::class, 'updateLeaveStatus']);
    
    // Profile Update
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);

    // Admin Only
    Route::middleware('role:admin')->group(function() {
        Route::get('/v4/users', [\App\Http\Controllers\Api\V4Controller::class, 'getUsers']);
        Route::post('/v4/users/{id}/suspend', [\App\Http\Controllers\Api\V4Controller::class, 'suspendUser']);
        
        // Admin Excel Exports
        Route::get('/admin/export-monthly-report', [ExcelController::class, 'exportMonthlyReport']);
        Route::get('/admin/export-orders', [ExcelController::class, 'exportOrders']);
        Route::get('/admin/export-attendances-detailed', [ExcelController::class, 'exportEnhancedAttendances']);
        Route::get('/admin/attendances', [ExcelController::class, 'getAttendances']);
    });

    // Attendance
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance', [AttendanceController::class, 'index']);

    // Inventory
    Route::get('/items', [InventoryController::class, 'getItems']);
    Route::post('/inventory/opname', [InventoryController::class, 'storeOpname']);
    Route::post('/inventory/report', [InventoryController::class, 'storeReport']);

    // Chef
    Route::get('/recipes', [\App\Http\Controllers\Api\RecipeController::class, 'getRecipes']);
    Route::post('/recipes', [\App\Http\Controllers\Api\RecipeController::class, 'store']);
    Route::post('/recipes/{id}/ingredients', [\App\Http\Controllers\Api\RecipeController::class, 'addIngredient']);
    Route::delete('/recipes/{id}/ingredients/{ingredientId}', [\App\Http\Controllers\Api\RecipeController::class, 'removeIngredient']);
    Route::post('/chef/calculate-materials', [\App\Http\Controllers\Api\RecipeController::class, 'calculateMaterials']);

    // Admin Excel
    Route::post('/admin/import-items', [ExcelController::class, 'importItems']);
    Route::get('/admin/export-attendances', [ExcelController::class, 'exportAttendances']);
    Route::get('/admin/export-opnames', [ExcelController::class, 'exportOpnames']);
});
