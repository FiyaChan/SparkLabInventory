<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StockMovementRequest;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    /**
     * "View Current Stock" — a single table of every product's live quantity,
     * separate from the Product CRUD screens since staff use this daily
     * while only admins touch product CRUD.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $filter = $request->input('filter', $request->input('status'));
        $search = $request->input('search');

        $products = Product::with(['inventory', 'category'])
            ->whereHas('inventory') // only products that have an inventory row
            ->when($filter === 'low_stock', function ($query) {
                $query->whereHas('inventory', function ($q) {
                    $q->where('quantity_on_hand', '>', 0)
                      ->whereColumn('quantity_on_hand', '<=', 'reorder_level');
                });
            })
            ->when($filter === 'out_of_stock', function ($query) {
                $query->whereHas('inventory', function ($q) {
                    $q->where('quantity_on_hand', '<=', 0);
                });
            })
            ->when($filter === 'in_stock', function ($query) {
                $query->whereHas('inventory', function ($q) {
                    $q->whereColumn('quantity_on_hand', '>', 'reorder_level');
                });
            })
            ->when($search, function ($query) use ($search) {
                $term = "%{$search}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                      ->orWhere('sku', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inventory.index', compact('products', 'filter', 'search'));
    }

    /**
     * Shows the stock-in/out/adjustment form for one product, plus its
     * movement history — this single screen satisfies "Stock In", "Stock Out",
     * "Inventory Adjustment", and "Stock History" all at once.
     */
    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load('inventory');

        $movements = $product->stockMovements()
            ->with('performedBy:id,name')
            ->latest()
            ->paginate(20);

        return view('admin.inventory.show', compact('product', 'movements'));
    }

    public function recordMovement(StockMovementRequest $request, Product $product)
    {
        $validated = $request->validated();

        // All the heavy lifting (locking, ledger entry, low-stock notification)
        // lives in StockService — this controller method is intentionally thin.
        $this->stockService->recordMovement(
            $product,
            $validated['type'],
            $validated['quantity'],
            $validated['reason'],
            Auth::user()
        );

        return back()->with('status', 'Stock movement recorded successfully.');
    }
}
