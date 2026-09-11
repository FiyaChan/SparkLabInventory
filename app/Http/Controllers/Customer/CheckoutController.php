<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ToyyibPayService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService,
        protected ToyyibPayService $toyyibPayService
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
                'buyer_tin' => $validated['buyer_tin'] ?? null,
                'buyer_id_type' => $validated['buyer_id_type'] ?? null,
                'buyer_id_number' => $validated['buyer_id_number'] ?? null,
                'buyer_sst_no' => $validated['buyer_sst_no'] ?? null,
                'require_einvoice' => ! empty($validated['require_einvoice']),
            ],
            $validated['payment_method']
        );

        // If ToyyibPay is chosen, create the online bill and redirect customer to payment page
        if ($validated['payment_method'] === 'toyyibpay') {
            try {
                $billCode = $this->toyyibPayService->createBill($order);
                $paymentUrl = $this->toyyibPayService->getBillPaymentUrl($billCode);

                return redirect()->away($paymentUrl);
            } catch (Exception $e) {
                Log::error('ToyyibPay checkout error: ' . $e->getMessage(), ['order_id' => $order->id]);

                return redirect()->route('orders.show', $order)
                    ->with('status', 'Order created, but we could not connect to ToyyibPay right now. Please retry payment below.');
            }
        }

        return redirect()->route('orders.show', $order)->with('status', 'Order placed successfully!');
    }
}
