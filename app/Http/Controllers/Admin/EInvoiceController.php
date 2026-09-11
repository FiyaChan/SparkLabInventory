<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EInvoice;
use App\Models\Order;
use App\Services\EInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EInvoiceController extends Controller
{
    public function __construct(protected EInvoiceService $eInvoiceService) {}

    /**
     * Display e-Invoices management console.
     */
    public function index(Request $request)
    {
        $this->authorizeEInvoiceAccess();

        $query = EInvoice::with(['order.user', 'order.payment'])->latest('issued_at');

        // Filter by source (pos / ecommerce / manual)
        if ($request->filled('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        // Filter by status (valid, pending, cancelled, etc.)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by type (01, consolidated)
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('invoice_type', $request->type);
        }

        // Search by invoice number, UUID, or customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('irbm_unique_id', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_tin', 'like', "%{$search}%");
            });
        }

        $eInvoices = $query->paginate(20)->withQueryString();

        // Metrics Summary
        $stats = [
            'total_count' => EInvoice::count(),
            'valid_count' => EInvoice::where('status', 'valid')->count(),
            'pos_count' => EInvoice::where('source', 'pos')->count(),
            'ecommerce_count' => EInvoice::where('source', 'ecommerce')->count(),
            'total_value' => EInvoice::where('status', 'valid')->sum('total_payable_amount'),
            'consolidated_count' => EInvoice::where('invoice_type', 'consolidated')->count(),
        ];

        return view('admin.einvoices.index', compact('eInvoices', 'stats'));
    }

    /**
     * View detailed e-Invoice with UBL 2.1 JSON inspect mode.
     */
    public function show(EInvoice $einvoice)
    {
        $this->authorizeEInvoiceAccess();

        $einvoice->load(['order.items.product', 'order.payment', 'order.user']);

        return view('admin.einvoices.show', compact('einvoice'));
    }

    /**
     * Download PDF format of the e-Invoice.
     */
    public function downloadPdf(EInvoice $einvoice)
    {
        $this->authorizeEInvoiceAccess();

        $order = $einvoice->order;
        if (! $order) {
            // If it's a consolidated invoice or standalone, create a mock order view container
            $pdf = Pdf::loadView('invoices.pdf', [
                'order' => (object) [
                    'order_number' => $einvoice->invoice_number,
                    'created_at' => $einvoice->issued_at,
                    'shipping_name' => $einvoice->buyer_name,
                    'shipping_phone' => $einvoice->buyer_phone,
                    'shipping_address' => $einvoice->buyer_address,
                    'total_amount' => $einvoice->total_payable_amount,
                    'eInvoice' => $einvoice,
                    'items' => collect($einvoice->ubl_payload['InvoiceLines'] ?? [])->map(function ($l) {
                        return (object) [
                            'product' => (object) ['name' => $l['product_name'] ?? 'Product', 'sku' => $l['product_sku'] ?? '-'],
                            'variant_name' => null,
                            'quantity' => $l['quantity'] ?? 1,
                            'unit_price' => $l['unit_price'] ?? 0,
                            'subtotal' => $l['subtotal'] ?? 0,
                        ];
                    }),
                    'payment' => (object) ['method' => 'N/A', 'status' => 'paid', 'transaction_ref' => $einvoice->irbm_unique_id],
                ],
            ]);
            return $pdf->download("einvoice-{$einvoice->invoice_number}.pdf");
        }

        $order->load(['items.product', 'items.variation', 'payment', 'user', 'eInvoice']);
        $pdf = Pdf::loadView('invoices.pdf', compact('order'));

        return $pdf->download("einvoice-{$einvoice->invoice_number}.pdf");
    }

    /**
     * Show Consolidated e-Invoice generator modal / page.
     */
    public function consolidatedView()
    {
        $this->authorizeEInvoiceAccess();

        // Get un-invoiced completed orders count for this month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $eligibleOrders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->whereDoesntHave('eInvoice', function ($q) {
                $q->where('invoice_type', '01');
            })
            ->with(['items.product', 'payment', 'user'])
            ->get();

        return view('admin.einvoices.consolidated', compact('eligibleOrders', 'startOfMonth', 'endOfMonth'));
    }

    /**
     * Process Consolidated e-Invoice batch creation.
     */
    public function generateConsolidated(Request $request)
    {
        $this->authorizeEInvoiceAccess();

        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $consolidated = $this->eInvoiceService->generateConsolidatedInvoice($startDate, $endDate);

            return redirect()->route('admin.einvoices.show', $consolidated)
                ->with('status', "Consolidated e-Invoice {$consolidated->invoice_number} successfully generated & validated!");
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Resync / re-simulate validation for an e-invoice.
     */
    public function resync(EInvoice $einvoice)
    {
        $this->authorizeEInvoiceAccess();

        $einvoice->update([
            'status' => 'valid',
            'validated_at' => now(),
            'lhdn_response' => array_merge($einvoice->lhdn_response ?? [], [
                'status' => 'Valid',
                'resyncedAt' => now()->toIso8601String(),
                'validationResults' => ['status' => 'Valid', 'warnings' => [], 'errors' => []],
            ]),
        ]);

        return back()->with('status', "e-Invoice {$einvoice->invoice_number} re-validated successfully.");
    }

    /**
     * Cancel an e-Invoice.
     */
    public function cancel(EInvoice $einvoice)
    {
        $this->authorizeEInvoiceAccess();

        $einvoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('status', "e-Invoice {$einvoice->invoice_number} has been cancelled.");
    }

    /**
     * Enforce admin / staff permission.
     */
    protected function authorizeEInvoiceAccess(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('order.view') && ! $user->hasAnyRole(['admin', 'superadmin', 'staff']))) {
            abort(403, 'Unauthorized access to e-Invoice module.');
        }
    }
}
