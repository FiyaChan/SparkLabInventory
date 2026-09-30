<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index()
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());

        // eager-load product, inventory, variations, images
        $cart->load('items.product.inventory', 'items.product.primaryImage', 'items.variation');

        return view('customer.cart.index', compact('cart'));
    }

    public function store(AddToCartRequest $request)
    {
        $validated = $request->validated();
        $product = Product::findOrFail($validated['product_id']);

        $this->cartService->addItem(
            Auth::user(),
            $product,
            (int) $validated['quantity'],
            $validated['variation_id'] ?? null
        );

        return back()->with('status', 'Added to cart.');
    }

    public function update(UpdateCartItemRequest $request, $item)
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());
        
        $cartItemId = null;
        if ($item instanceof CartItem) {
            $cartItemId = $item->id;
        } elseif ($item instanceof Product) {
            $cartItemId = $cart->items()->where('product_id', $item->id)->value('id');
        } elseif (is_numeric($item)) {
            $cartItemId = (int) $item;
        }

        if ($cartItemId) {
            $this->cartService->updateQuantity(Auth::user(), $cartItemId, (int) $request->validated()['quantity']);
        }

        return back()->with('status', 'Cart updated.');
    }

    public function destroy($item)
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());

        $cartItemId = null;
        if ($item instanceof CartItem) {
            $cartItemId = $item->id;
        } elseif ($item instanceof Product) {
            $cartItemId = $cart->items()->where('product_id', $item->id)->value('id');
        } elseif (is_numeric($item)) {
            $cartItemId = (int) $item;
        }

        if ($cartItemId) {
            $this->cartService->removeItem(Auth::user(), $cartItemId);
        }

        return back()->with('status', 'Item removed from cart.');
    }
}
