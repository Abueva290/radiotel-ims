<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\RepairJobController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Every logged-in user can change their own password
    Route::get('/change-password', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/change-password', [PasswordChangeController::class, 'update'])->name('password.change.store');

    // Secretary + Admin
    Route::middleware('role:admin,secretary')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/receivables/{receivable}', [ReceivableController::class, 'show'])->name('receivables.show');
        Route::post('/receivables/{receivable}/payment', [ReceivableController::class, 'storePayment'])->name('receivables.payment');

        Route::get('/payables', [PayableController::class, 'index'])->name('payables.index');
        Route::get('/payables/create', [PayableController::class, 'create'])->name('payables.create');
        Route::post('/payables', [PayableController::class, 'store'])->name('payables.store');
        Route::get('/payables/{payable}', [PayableController::class, 'show'])->name('payables.show');
        Route::post('/payables/{payable}/payment', [PayableController::class, 'storePayment'])->name('payables.payment');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/archive', [CustomerController::class, 'toggleArchive'])->name('customers.archive');
    });

    // Staff + Admin
    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/inventory', [ProductController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/create', [ProductController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [ProductController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{product}/edit', [ProductController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{product}', [ProductController::class, 'update'])->name('inventory.update');
        Route::post('/inventory/{product}/movement', [ProductController::class, 'storeMovement'])->name('inventory.movement');
    });

    // Technical Head + Admin
    Route::middleware('role:admin,technical_head')->group(function () {
        Route::get('/repairs', [RepairJobController::class, 'index'])->name('repairs.index');
        Route::get('/repairs/create', [RepairJobController::class, 'create'])->name('repairs.create');
        Route::post('/repairs', [RepairJobController::class, 'store'])->name('repairs.store');
        Route::get('/repairs/{repair}', [RepairJobController::class, 'show'])->name('repairs.show');
        Route::post('/repairs/{repair}/parts', [RepairJobController::class, 'storePart'])->name('repairs.parts');
        Route::patch('/repairs/{repair}/status', [RepairJobController::class, 'updateStatus'])->name('repairs.status');
    });

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::view('/reports', 'placeholder', ['title' => 'Reports & Analytics'])->name('reports.index');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
        Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    });

    // Breeze profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';