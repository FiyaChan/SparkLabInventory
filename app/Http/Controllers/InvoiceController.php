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

        $order->load('items.product', 'payment', 'user');

        $pdf = Pdf::loadView('invoices.pdf', compact('order'));

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }
}
