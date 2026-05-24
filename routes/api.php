<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CONTROLLERS
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SavingsGoalController;
use App\Http\Controllers\Api\RecurringTransactionController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\WalletMemberController;
use App\Http\Controllers\Api\InsightController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/register', [
    AuthController::class,
    'register'
]);

Route::post('/login', [
    AuthController::class,
    'login'
]);

Route::post('/login/google', [
    AuthController::class,
    'googleLogin'
]);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [
        AuthController::class,
        'profile'
    ]);

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ]);

    /*
    |--------------------------------------------------------------------------
    | WALLET
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'wallets',
        WalletController::class
    );

    /*
    |--------------------------------------------------------------------------
    | WALLET MEMBERS (Shared Wallet / Kolaborasi)
    |--------------------------------------------------------------------------
    */

    Route::get('/wallets/{wallet}/members', [
        WalletMemberController::class,
        'index'
    ]);

    Route::post('/wallets/{wallet}/members', [
        WalletMemberController::class,
        'store'
    ]);

    Route::delete('/wallets/{wallet}/members/{member}', [
        WalletMemberController::class,
        'destroy'
    ]);

    Route::get('/wallets/{wallet}/activity', [
        WalletMemberController::class,
        'activity'
    ]);

    /*
    |--------------------------------------------------------------------------
    | CATEGORY
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'categories',
        CategoryController::class
    );

    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'transactions',
        TransactionController::class
    );

    /*
    |--------------------------------------------------------------------------
    | BUDGET
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'budgets',
        BudgetController::class
    );

    /*
    |--------------------------------------------------------------------------
    | SAVINGS GOALS
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'savings-goals',
        SavingsGoalController::class
    );

    /*
    |--------------------------------------------------------------------------
    | RECURRING TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'recurring-transactions',
        RecurringTransactionController::class
    );

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [
        NotificationController::class,
        'index'
    ]);

    Route::put('/notifications/{id}/read', [
        NotificationController::class,
        'read'
    ]);

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    Route::get('/statistics/monthly', [
        StatisticsController::class,
        'monthly'
    ]);

    Route::get('/statistics/weekly', [
        StatisticsController::class,
        'weekly'
    ]);

    Route::get('/statistics/category', [
        StatisticsController::class,
        'category'
    ]);

    /*
    |--------------------------------------------------------------------------
    | EXPORT
    |--------------------------------------------------------------------------
    */

    Route::get('/export', [
        ExportController::class,
        'export'
    ]);

    /*
    |--------------------------------------------------------------------------
    | INSIGHTS (Analisis Keuangan Otomatis)
    |--------------------------------------------------------------------------
    */

    Route::get('/insights', [
        InsightController::class,
        'index'
    ]);
});