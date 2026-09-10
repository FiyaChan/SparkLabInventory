<?php

use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// CUSTOMER
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');

    // throttle:10,1 — a customer accidentally double-submitting checkout
    // (e.g. double-clicking "Place Order") shouldn't be able to spam
    // order creation attempts either.
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/invoice', [InvoiceController::class, 'download'])->name('orders.invoice');
});

// ---------------------------------------------------------------------
// ADMIN / STAFF
// ---------------------------------------------------------------------
Route::middleware(['auth', 'role:admin|staff|superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.status');

        // Point of Sale (POS) Cashier System
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('pos/search-products', [PosController::class, 'searchProducts'])->name('pos.search-products');
        Route::get('pos/search-customers', [PosController::class, 'searchCustomers'])->name('pos.search-customers');
        Route::post('pos/checkout', [PosController::class, 'checkout'])
            ->middleware('throttle:30,1')
            ->name('pos.checkout');
        Route::get('pos/orders/{order}/receipt', [PosController::class, 'receipt'])->name('pos.receipt');
        Route::get('pos/orders/{order}/receipt-pdf', [PosController::class, 'receiptPdf'])->name('pos.receipt.pdf');
    });
