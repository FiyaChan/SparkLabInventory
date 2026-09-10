<?php

use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|staff'])
    ->prefix('admin/reports')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('sales/export/pdf', [ReportController::class, 'salesExportPdf'])->name('sales.export.pdf');
        Route::get('sales/export/excel', [ReportController::class, 'salesExportExcel'])->name('sales.export.excel');

        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('inventory/export/pdf', [ReportController::class, 'inventoryExportPdf'])->name('inventory.export.pdf');
        Route::get('inventory/export/excel', [ReportController::class, 'inventoryExportExcel'])->name('inventory.export.excel');

        Route::get('customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('customers/export/excel', [ReportController::class, 'customersExportExcel'])->name('customers.export.excel');
    });
