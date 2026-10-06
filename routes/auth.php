<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication routes
|--------------------------------------------------------------------------
| Only log in and log out are used. The other Laravel Breeze features were
| removed on purpose:
| - Public registration: the Operational Manager creates employee accounts
|   in User Management.
| - Forgot / reset password by email: the system has no mail server; the
|   Operational Manager resets passwords in User Management, and the employee
|   is then required to set a new one (Change Password).
| - Email verification: accounts are created by the admin, so emails are trusted.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});