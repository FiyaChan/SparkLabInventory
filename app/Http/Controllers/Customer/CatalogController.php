<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['primaryImage', 'category', 'inventory'])
            ->where('is_active', true)
            ->when($request->filled('search'), function ($query) use ($request) {
                // Parameterized 'where...like' via Eloquent — the search term is
                // bound as a parameter, never concatenated into raw SQL, so
                // characters like ' OR '1'='1 in the search box can't break out
                // of the query. This is the concrete mechanism behind
                // "SQL Injection Prevention using Eloquent ORM" in your report.
                $query->where('name', 'like', '%'.$request->input('search').'%');
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $request->input('category')));
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                match ($request->input('sort')) {
                    'price_asc' => $query->orderBy('price', 'asc'),
                    'price_desc' => $query->orderBy('price', 'desc'),
                    default => $query->latest(),
                };
            }, fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        $categories = Category::where('is_active', true)->whereNull('parent_id')->get();

        return view('customer.catalog.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        // A discontinued/inactive product returns 404 rather than showing it —
        // simple but effective: don't let customers view or link to products
        // that shouldn't be purchasable.
        abort_unless($product->is_active, 404);

        $product->load(['images', 'category', 'inventory']);

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        return view('customer.catalog.show', compact('product', 'related'));
    }
}
