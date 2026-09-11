<?php

namespace App\Http\Controllers;

use App\Models\EInvoice;
use Illuminate\Http\Request;

class EInvoiceVerificationController extends Controller
{
    /**
     * Public verification endpoint for QR code scan.
     */
    public function verify(string $uuid)
    {
        $eInvoice = EInvoice::where('irbm_unique_id', $uuid)
            ->orWhere('invoice_number', $uuid)
            ->with(['order.items.product', 'order.payment'])
            ->firstOrFail();

        return view('einvoices.verify', compact('eInvoice'));
    }
}
