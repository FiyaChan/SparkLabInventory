@extends('layouts.shop')

@section('content')
<div class="mb-4">
    <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-box-arrow-up-right me-2" style="color: #7E22CE;"></i>Order Checkout & Delivery</h4>
    <p class="text-muted small mb-0">Enter your delivery address and select a payment method for your science kits.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="sci-card p-4">
            <h5 class="sci-heading mb-4" style="color: #1E1B4B;"><i class="bi bi-geo-alt me-2 text-purple" style="color: #7E22CE;"></i>Delivery Details</h5>

            @if ($errors->any())
                <div class="sci-alert sci-alert-danger mb-4">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="shipping_name" class="sci-form-label">Recipient Name <span class="text-danger">*</span></label>
                    <input type="text" class="sci-form-control form-control" id="shipping_name" name="shipping_name" 
                           value="{{ old('shipping_name', auth()->user()->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="shipping_phone" class="sci-form-label">Contact Phone Number <span class="text-danger">*</span></label>
                    <input type="text" class="sci-form-control form-control" id="shipping_phone" name="shipping_phone" 
                           value="{{ old('shipping_phone') }}" placeholder="e.g. +60123456789" required>
                </div>

                <div class="mb-4">
                    <label for="shipping_address" class="sci-form-label">Home / School Delivery Address <span class="text-danger">*</span></label>
                    <textarea class="sci-form-control form-control" id="shipping_address" name="shipping_address" rows="3" placeholder="House/Apartment Number, Street Address, Postal Code, City..." required>{{ old('shipping_address') }}</textarea>
                </div>

                <div class="mb-4 p-3 rounded-3" style="background: #F8F5FF; border: 2px solid #E9D5FF;">
                    <label class="sci-form-label d-block mb-3" style="color: #1E1B4B;"><i class="bi bi-credit-card me-2 text-purple" style="color: #7E22CE;"></i>Payment Method</label>
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod" @checked(old('payment_method') === 'cod' || !old('payment_method'))>
                        <label class="form-check-label fw-bold text-dark" for="pay_cod">
                            Cash on Delivery (COD)
                            <div class="text-muted small fw-normal">Pay with cash directly upon package arrival at your doorstep.</div>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" id="pay_simulation" value="online_simulation" @checked(old('payment_method') === 'online_simulation')>
                        <label class="form-check-label fw-bold text-dark" for="pay_simulation">
                            Online Banking Simulation
                            <div class="text-muted small fw-normal">Instant test checkout simulation with instant confirmation.</div>
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-sci-primary btn-lg py-3 fs-6">
                        <i class="bi bi-stars me-1"></i>Confirm Order & Place Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="sci-glass-panel p-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-cart3 me-2 text-purple" style="color: #7E22CE;"></i>Order Items</h5>
            
            <div class="d-flex flex-column gap-3 mb-4">
                @foreach ($cart->items as $item)
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom" style="border-color: #EDE9FE !important;">
                        <div>
                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                            <small class="text-muted">RM {{ number_format($item->product->price, 2) }} &times; {{ $item->quantity }} items</small>
                        </div>
                        <span class="sci-price small">RM {{ number_format($item->quantity * $item->product->price, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-2 border-top d-flex justify-content-between align-items-baseline mb-4" style="border-color: #E9D5FF !important;">
                <span class="sci-heading fw-bold fs-5" style="color: #1E1B4B;">Total Due:</span>
                <span class="sci-price fs-2">RM {{ number_format($cart->total, 2) }}</span>
            </div>

            <div class="d-grid">
                <a href="{{ route('cart.index') }}" class="btn btn-sci-outline btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Cart
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
