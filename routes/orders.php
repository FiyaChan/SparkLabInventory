<?php

use App\Http\Controllers\Admin\EInvoiceController;
use App\Http\Controllers\Admin\EInvoiceSettingController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ToyyibPayController;
use App\Http\Controllers\EInvoiceVerificationController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// PUBLIC ENDPOINTS: TOYYIBPAY GATEWAY & LHDN QR VERIFICATION
// ---------------------------------------------------------------------
Route::get('/payment/toyyibpay/return', [ToyyibPayController::class, 'handleReturn'])->name('toyyibpay.return');
Route::post('/payment/toyyibpay/callback', [ToyyibPayController::class, 'handleCallback'])->name('toyyibpay.callback');
Route::get('/einvoice/verify/{uuid}', [EInvoiceVerificationController::class, 'verify'])->name('einvoice.verify');

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
    Route::get('/orders/{order}/pay', [ToyyibPayController::class, 'retryPayment'])->name('orders.pay');
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

        // LHDN e-Invoicing Management
        Route::get('e-invoices', [EInvoiceController::class, 'index'])->name('einvoices.index');
        Route::get('e-invoices/consolidated', [EInvoiceController::class, 'consolidatedView'])->name('einvoices.consolidated');
        Route::post('e-invoices/consolidated', [EInvoiceController::class, 'generateConsolidated'])->name('einvoices.consolidated.generate');
        Route::get('e-invoices/settings', [EInvoiceSettingController::class, 'index'])->name('einvoices.settings');
        Route::put('e-invoices/settings', [EInvoiceSettingController::class, 'update'])->name('einvoices.settings.update');
        Route::get('e-invoices/{einvoice}', [EInvoiceController::class, 'show'])->name('einvoices.show');
        Route::get('e-invoices/{einvoice}/pdf', [EInvoiceController::class, 'downloadPdf'])->name('einvoices.pdf');
        Route::post('e-invoices/{einvoice}/resync', [EInvoiceController::class, 'resync'])->name('einvoices.resync');
        Route::post('e-invoices/{einvoice}/cancel', [EInvoiceController::class, 'cancel'])->name('einvoices.cancel');
    });
