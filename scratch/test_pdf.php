<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

$orders = Order::with('user')->where('status', 'completed')->get();
$summary = [
    'total_revenue' => $orders->sum('total_amount'),
    'total_orders' => $orders->count(),
];
$from = now()->startOfMonth();
$to = now();

$pdf = Pdf::loadView('admin.reports.sales-pdf', compact('orders', 'summary', 'from', 'to'));
$output = $pdf->output();

echo 'SALES_PDF_GENERATED_BYTES: ' . strlen($output) . PHP_EOL;

$products = Product::with(['category', 'inventory'])->get();
$pdfInv = Pdf::loadView('admin.reports.inventory-pdf', compact('products'));
$outputInv = $pdfInv->output();
echo 'INVENTORY_PDF_GENERATED_BYTES: ' . strlen($outputInv) . PHP_EOL;
