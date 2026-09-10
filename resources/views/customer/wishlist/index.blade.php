@extends('layouts.shop')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-heart-fill me-2" style="color: #EC4899;"></i>My Dream Science Kits (Wishlist)</h4>
        <p class="text-muted small mb-0">Saved STEM projects and experiment sets for your upcoming birthday or science fair!</p>
    </div>
    <a href="{{ route('shop.index') }}" class="btn btn-sci-outline btn-sm">
        <i class="bi bi-stars me-1 text-warning"></i>Discover More Kits
    </a>
</div>

@if ($wishlists->isEmpty())
    <div class="sci-glass-panel text-center py-5">
        <i class="bi bi-heart fs-1 d-block mb-3" style="color: #EC4899;"></i>
        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">Your Dream Wishlist is Empty</h4>
        <p class="text-muted small mb-4">Explore our kits and save the ones you can't wait to build!</p>
        <a href="{{ route('shop.index') }}" class="btn btn-sci-primary">
            <i class="bi bi-stars me-1"></i>Explore All Science Kits
        </a>
    </div>
@else
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        @foreach ($wishlists as $wishlist)
            @php 
                $product = $wishlist->product;
                $qty = $product->inventory->quantity_on_hand ?? 0;
            @endphp
            <div class="col">
                <div class="sci-card h-100 d-flex flex-column">
                    <div class="position-relative overflow-hidden" style="height: 190px; background: #F3E8FF;">
                        @if ($product->primaryImage)
                            <img src="{{ Storage::url($product->primaryImage->path) }}" class="w-100 h-100 object-fit-cover" alt="{{ $product->name }}">
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                <i class="bi bi-stars fs-2" style="color: #7E22CE;"></i>
                            </div>
                        @endif
                        <div class="position-absolute top-0 end-0 p-2">
                            @if ($qty <= 0)
                                <span class="sci-badge sci-badge-danger">Sold Out</span>
                            @else
                                <span class="sci-badge sci-badge-purple">In Stock</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <h6 class="sci-heading fw-bold mb-1">
                            <a href="{{ route('shop.show', $product) }}" class="text-dark text-decoration-none hover-purple">
                                {{ $product->name }}
                            </a>
                        </h6>
                        <div class="sci-price mb-3">RM {{ number_format($product->price, 2) }}</div>

                        <div class="mt-auto d-grid gap-2">
                            @if ($qty > 0)
                                <form method="POST" action="{{ route('cart.store') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-sci-primary btn-sm w-100">
                                        <i class="bi bi-cart3 me-1"></i>Add to Cart
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('wishlist.destroy', $product) }}" onsubmit="return confirm('Remove this kit from your wishlist?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sci-secondary btn-sm text-danger w-100">
                                    <i class="bi bi-trash me-1"></i>Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($wishlists->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $wishlists->links() }}
        </div>
    @endif
@endif
@endsection
