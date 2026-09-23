<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Secretary + Admin
    Route::middleware('role:admin,secretary')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('/receivables/{receivable}', [ReceivableController::class, 'show'])->name('receivables.show');
        Route::post('/receivables/{receivable}/payment', [ReceivableController::class, 'storePayment'])->name('receivables.payment');

        Route::view('/payables', 'placeholder', ['title' => 'Accounts Payable'])->name('payables.index');
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
        Route::view('/repairs', 'placeholder', ['title' => 'Repair & Service Management'])->name('repairs.index');
    });

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::view('/reports', 'placeholder', ['title' => 'Reports & Analytics'])->name('reports.index');
        Route::view('/users', 'placeholder', ['title' => 'User Management'])->name('users.index');
    });

    // Breeze profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';