<?php

use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

// role:admin,staff -> both can manage products/stock day-to-day.
// Fine-grained restriction (e.g. only admin can delete) is enforced inside
// the controller via $this->authorize(), backed by ProductPolicy + permissions —
// the route middleware is a coarse first gate, the policy is the real gate.
Route::middleware(['auth', 'role:admin|staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('products', ProductController::class);

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::post('inventory/{product}/movement', [InventoryController::class, 'recordMovement'])
            ->name('inventory.movement');
    });
