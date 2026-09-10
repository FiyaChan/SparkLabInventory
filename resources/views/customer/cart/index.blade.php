@extends('layouts.shop')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-cart3 me-2" style="color: #7E22CE;"></i>My Cart</h4>
        <p class="text-muted small mb-0">Review your selected science experiment kits, quantities, and order total.</p>
    </div>
    <a href="{{ route('shop.index') }}" class="btn btn-sci-outline btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add More Kits
    </a>
</div>

@if ($errors->any())
    <div class="sci-alert sci-alert-danger mb-4">
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($cart->items->isEmpty())
    <div class="sci-glass-panel text-center py-5">
        <i class="bi bi-cart3 fs-1 d-block mb-3" style="color: #7E22CE;"></i>
        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">Your Cart is Empty</h4>
        <p class="text-muted small mb-4">No science kits currently in your cart. Start exploring our exciting STEM collection!</p>
        <a href="{{ route('shop.index') }}" class="btn btn-sci-primary">
            <i class="bi bi-stars me-1"></i>Browse All Science Kits
        </a>
    </div>
@else
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="sci-card">
                <div class="table-responsive">
                    <table class="table sci-table align-middle">
                        <thead>
                            <tr>
                                <th>Science Kit</th>
                                <th>Price</th>
                                <th style="width: 140px;">Quantity</th>
                                <th>Subtotal</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cart->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold fs-6" style="color: #1E1B4B;">{{ $item->product->name }}</div>
                                        <small class="text-muted">Kit SKU: <code class="p-1 rounded" style="background: #F1F5F9; color: #475569;">{{ $item->product->sku }}</code></small>
                                    </td>
                                    <td class="fw-semibold">RM {{ number_format($item->product->price, 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('cart.update', $item->product) }}" class="d-flex gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1"
                                                   max="{{ $item->product->inventory->quantity_on_hand ?? 0 }}"
                                                   class="sci-form-control form-control form-control-sm text-center">
                                            <button class="btn btn-sci-outline btn-sm px-2" title="Update Quantity">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="sci-price">RM {{ number_format($item->quantity * $item->product->price, 2) }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('cart.destroy', $item->product) }}"
                                              onsubmit="return confirm('Remove this kit from your box?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-sci-secondary text-danger" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sci-glass-panel p-4">
                <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-receipt me-2" style="color: #7E22CE;"></i>Order Summary</h5>
                
                <div class="d-flex justify-content-between mb-2 small fw-semibold">
                    <span class="text-muted">Kits Subtotal:</span>
                    <span style="color: #1E1B4B;">RM {{ number_format($cart->total, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3 small fw-semibold">
                    <span class="text-muted">Explorer Box Shipping:</span>
                    <span class="text-success fw-bold">FREE Delivery</span>
                </div>

                <div class="pt-3 border-top d-flex justify-content-between align-items-baseline mb-4" style="border-color: #E9D5FF !important;">
                    <span class="sci-heading fw-bold fs-5" style="color: #1E1B4B;">Total Amount:</span>
                    <span class="sci-price fs-3">RM {{ number_format($cart->total, 2) }}</span>
                </div>

                <div class="d-grid">
                    <a href="{{ route('checkout.index') }}" class="btn btn-sci-primary py-3 text-center fs-6">
                        <i class="bi bi-bag-check-fill me-1"></i>Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
