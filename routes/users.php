<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

// role:admin ONLY — note this differs from products/orders/inventory, which
// allow 'staff' too. User & role management is intentionally the one area
// staff can never touch, regardless of any individual permission grant —
// this is a hard-coded boundary, not just a seeded-permission default.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.role');
        Route::patch('users/{user}/status', [UserManagementController::class, 'toggleStatus'])->name('users.status');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
