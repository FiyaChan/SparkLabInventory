<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        // eager-load to avoid N+1 queries when the Blade view accesses
        // $product->category->name and $product->inventory->quantity_on_hand
        // for every row in the table.
        $products = Product::with(['category', 'inventory', 'primaryImage', 'variations'])
            ->when($request->filled('search'), function ($query) use ($request) {
                // Parameterized 'like' binding via Eloquent — never raw string
                // concatenation into SQL, which is what prevents SQL injection here.
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->input('search').'%')
                      ->orWhere('sku', 'like', '%'.$request->input('search').'%');
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('is_active', true)->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::where('is_active', true)->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // DB::transaction ensures product + inventory + images + variations are created
        // atomically — if image storage fails partway through, the product
        // row is rolled back too, rather than leaving an orphaned half-created product.
        $product = DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'cost_price' => $validated['cost_price'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            Inventory::create([
                'product_id' => $product->id,
                'quantity_on_hand' => $validated['initial_quantity'] ?? 0,
                'reorder_level' => $validated['reorder_level'] ?? 10,
            ]);

            if ($request->hasFile('images')) {
                $this->storeImages($product, $request->file('images'));
            }

            if ($request->filled('variations')) {
                foreach ($request->input('variations') as $varData) {
                    if (! empty($varData['name'])) {
                        ProductVariation::create([
                            'product_id' => $product->id,
                            'name' => $varData['name'],
                            'sku' => ! empty($varData['sku']) ? $varData['sku'] : ($product->sku.'-'.strtoupper(Str::random(4))),
                            'price' => $varData['price'] ?? $product->price,
                            'cost_price' => $varData['cost_price'] ?? null,
                            'stock' => $varData['stock'] ?? 0,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            return $product;
        });

        ActivityLog::record('product.created', ['product_id' => $product->id, 'sku' => $product->sku]);

        return redirect()->route('admin.products.index')->with('status', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['category', 'images', 'inventory', 'variations']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = Category::where('is_active', true)->get();
        $product->load(['images', 'variations']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $product) {
            $product->update([
                'category_id' => $validated['category_id'],
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'cost_price' => $validated['cost_price'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            if ($request->hasFile('images')) {
                $this->storeImages($product, $request->file('images'));
            }

            if ($request->has('variations')) {
                $submittedVariations = $request->input('variations', []);
                $keptIds = [];

                foreach ($submittedVariations as $varData) {
                    if (empty($varData['name'])) {
                        continue;
                    }

                    if (! empty($varData['id'])) {
                        $existing = ProductVariation::where('id', $varData['id'])
                            ->where('product_id', $product->id)
                            ->first();

                        if ($existing) {
                            $existing->update([
                                'name' => $varData['name'],
                                'sku' => ! empty($varData['sku']) ? $varData['sku'] : $existing->sku,
                                'price' => $varData['price'] ?? $product->price,
                                'cost_price' => $varData['cost_price'] ?? null,
                                'stock' => $varData['stock'] ?? 0,
                            ]);
                            $keptIds[] = $existing->id;
                            continue;
                        }
                    }

                    $newVar = ProductVariation::create([
                        'product_id' => $product->id,
                        'name' => $varData['name'],
                        'sku' => ! empty($varData['sku']) ? $varData['sku'] : ($product->sku.'-'.strtoupper(Str::random(4))),
                        'price' => $varData['price'] ?? $product->price,
                        'cost_price' => $varData['cost_price'] ?? null,
                        'stock' => $varData['stock'] ?? 0,
                        'is_active' => true,
                    ]);
                    $keptIds[] = $newVar->id;
                }

                $product->variations()->whereNotIn('id', $keptIds)->delete();
            }
        });

        ActivityLog::record('product.updated', ['product_id' => $product->id]);

        return redirect()->route('admin.products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Product model uses SoftDeletes (see migration) — this sets deleted_at
        // rather than removing the row, preserving referential integrity for
        // any order_items that reference this product's historical orders.
        $product->delete();

        ActivityLog::record('product.deleted', ['product_id' => $product->id, 'sku' => $product->sku]);

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    protected function storeImages(Product $product, array $files): void
    {
        foreach ($files as $index => $file) {
            // store() generates a random filename automatically — we NEVER trust
            // or use the client-provided original filename, which could otherwise
            // be crafted to overwrite another file or contain path traversal (../../).
            $path = $file->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_primary' => $index === 0 && $product->images()->count() === 0,
                'sort_order' => $product->images()->count(),
            ]);
        }
    }
}
