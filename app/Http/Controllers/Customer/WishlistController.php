<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with('product.primaryImage', 'product.inventory')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(12);

        return view('customer.wishlist.index', compact('wishlists'));
    }

    public function store(Product $product)
    {
        // firstOrCreate respects the DB-level unique(user_id, product_id) constraint —
        // even if two rapid double-clicks race each other, only one row is created
        // and no duplicate-key exception leaks to the user.
        Wishlist::firstOrCreate([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return back()->with('status', 'Added to wishlist.');
    }

    public function destroy(Product $product)
    {
        Wishlist::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();

        return back()->with('status', 'Removed from wishlist.');
    }
}
