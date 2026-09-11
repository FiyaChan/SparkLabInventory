<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PosCheckoutRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PosController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Display the Point of Sale Terminal Interface.
     */
    public function index(Request $request)
    {
        $this->authorizePosAccess();

        $categories = Category::withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->orderBy('name')->get();

        $products = Product::with(['inventory', 'primaryImage', 'category', 'variations'])
            ->where('is_active', true)
            ->latest()
            ->paginate(30);

        $walkinCustomer = User::firstOrCreate(
            ['email' => 'walkin@pos.local'],
            [
                'name' => 'Walk-in Customer',
                'password' => bcrypt('WalkinCustomerPos123!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        return view('admin.pos.terminal', compact('categories', 'products', 'walkinCustomer'));
    }

    /**
     * Search products for live catalog filtering and barcode scan.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorizePosAccess();

        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $query = Product::with(['inventory', 'primaryImage', 'category', 'variations'])
            ->where('is_active', true);

        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->take(60)->get()->map(function ($product) {
            $imagePath = null;
            if ($product->primaryImage && $product->primaryImage->path) {
                $imagePath = Storage::url($product->primaryImage->path);
            }
            $stock = $product->inventory ? $product->inventory->quantity_on_hand : 0;
            $reorderLevel = $product->inventory ? $product->inventory->reorder_level : 5;

            $variations = $product->variations->map(function ($v) use ($product) {
                return [
                    'id' => $v->id,
                    'product_id' => $product->id,
                    'name' => $v->name,
                    'sku' => $v->sku ?: ($product->sku . '-' . $v->id),
                    'price' => (float) $v->price,
                    'formatted_price' => 'RM ' . number_format($v->price, 2),
                    'stock' => (int) $v->stock,
                    'is_in_stock' => $v->stock > 0,
                ];
            });

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'formatted_price' => 'RM ' . number_format($product->price, 2),
                'stock' => $stock,
                'reorder_level' => $reorderLevel,
                'category_name' => $product->category ? $product->category->name : 'Uncategorized',
                'image_url' => $imagePath,
                'is_in_stock' => $stock > 0,
                'is_low_stock' => $stock > 0 && $stock <= $reorderLevel,
                'has_variations' => $variations->isNotEmpty(),
                'variations' => $variations,
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * Search customers for cashier customer assignment.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $this->authorizePosAccess();

        $query = $request->input('query');
        if (strlen($query) < 2) {
            return response()->json(['customers' => []]);
        }

        $customers = User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%");
        })
        ->where('is_active', true)
        ->take(10)
        ->get(['id', 'name', 'email', 'phone']);

        return response()->json(['customers' => $customers]);
    }

    /**
     * Handle POS Checkout submission.
     */
    public function checkout(PosCheckoutRequest $request): JsonResponse
    {
        try {
            $result = $this->posService->processSale($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Order {$result['order']->order_number} completed successfully!",
                'order' => [
                    'id' => $result['order']->id,
                    'order_number' => $result['order']->order_number,
                    'total_amount' => $result['grand_total'],
                    'subtotal' => $result['subtotal'],
                    'discount' => $result['discount'],
                    'tendered_amount' => $result['tendered_amount'],
                    'change_due' => $result['change_due'],
                    'payment_method' => $result['payment_method'],
                    'created_at' => $result['order']->created_at->format('d M Y, h:i A'),
                ],
                'receipt_url' => route('admin.pos.receipt', $result['order']->id),
                'pdf_url' => route('admin.pos.receipt.pdf', $result['order']->id),
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during POS transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Render printable thermal receipt view.
     */
    public function receipt(Order $order)
    {
        $this->authorizePosAccess();

        $order->load(['items.product', 'payment', 'user', 'eInvoice']);

        return view('admin.pos.receipt', compact('order'));
    }

    /**
     * Export / Download Receipt PDF.
     */
    public function receiptPdf(Order $order)
    {
        $this->authorizePosAccess();

        $order->load(['items.product', 'payment', 'user', 'eInvoice']);

        $pdf = Pdf::loadView('admin.pos.receipt', [
            'order' => $order,
            'isPdf' => true,
        ])->setPaper([0, 0, 226.77, 650], 'portrait'); // 80mm width thermal paper

        return $pdf->download("receipt-{$order->order_number}.pdf");
    }

    /**
     * Authorize user for POS module access.
     */
    protected function authorizePosAccess(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->can('pos.access') && ! $user->hasAnyRole(['admin', 'superadmin', 'staff']))) {
            abort(403, 'Unauthorized access to Point of Sale module.');
        }
    }
}
