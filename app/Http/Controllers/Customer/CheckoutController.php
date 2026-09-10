<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService
    ) {}

    public function index()
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());
        $cart->load('items.product.inventory');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        return view('customer.checkout.index', compact('cart'));
    }

    public function store(CheckoutRequest $request)
    {
        $cart = $this->cartService->getOrCreateCart(Auth::user());
        $validated = $request->validated();

        // Any ValidationException thrown inside OrderService::placeOrder()
        // (e.g. stock ran out mid-checkout, or a product was deactivated
        // between "add to cart" and "checkout") bubbles up naturally and
        // Laravel redirects back with the error — no special handling needed here.
        $order = $this->orderService->placeOrder(
            Auth::user(),
            $cart,
            [
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
            ],
            $validated['payment_method']
        );

        return redirect()->route('orders.show', $order)->with('status', 'Order placed successfully!');
    }
}
