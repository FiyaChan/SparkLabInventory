<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function download(Order $order)
    {
        // Same ownership check as OrderController::show() — an invoice is
        // just another view of order data, so it needs the identical IDOR
        // protection. Staff/admin (order.view permission) can also pull
        // any invoice for customer support purposes.
        abort_unless(
            $order->user_id === Auth::id() || Auth::user()->can('order.view'),
            403
        );

        $order->load('items.product', 'items.variation', 'payment', 'user', 'eInvoice');

        // If order does not have an e-invoice record yet, generate one on-demand
        if (! $order->eInvoice) {
            try {
                $eInvoiceService = app(\App\Services\EInvoiceService::class);
                $eInvoiceService->generateForOrder($order);
                $order->load('eInvoice');
            } catch (\Throwable $e) {
                // Ignore failure and continue rendering
            }
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('order'));

        return $pdf->download("einvoice-{$order->order_number}.pdf");
    }
}
