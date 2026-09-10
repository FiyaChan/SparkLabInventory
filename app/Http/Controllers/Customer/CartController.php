<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index()
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());

        // eager-load product + its inventory so the Blade view can show
        // live stock availability next to each cart line without N+1 queries
        $cart->load('items.product.inventory', 'items.product.primaryImage');

        return view('customer.cart.index', compact('cart'));
    }

    public function store(AddToCartRequest $request)
    {
        $product = Product::findOrFail($request->validated()['product_id']);

        $this->cartService->addItem(Auth::user(), $product, $request->validated()['quantity']);

        return back()->with('status', 'Added to cart.');
    }

    public function update(UpdateCartItemRequest $request, Product $product)
    {
        $this->cartService->updateQuantity(Auth::user(), $product, $request->validated()['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function destroy(Product $product)
    {
        $this->cartService->removeItem(Auth::user(), $product);

        return back()->with('status', 'Item removed from cart.');
    }
}
