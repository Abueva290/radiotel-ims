<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Secretary + Admin
    Route::middleware('role:admin,secretary')->group(function () {
        Route::view('/sales', 'placeholder', ['title' => 'Sales Management'])->name('sales.index');
        Route::view('/receivables', 'placeholder', ['title' => 'Accounts Receivable'])->name('receivables.index');
        Route::view('/payables', 'placeholder', ['title' => 'Accounts Payable'])->name('payables.index');
    });

    // Staff + Admin
    Route::middleware('role:admin,staff')->group(function () {
        Route::view('/inventory', 'placeholder', ['title' => 'Inventory Management'])->name('inventory.index');
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