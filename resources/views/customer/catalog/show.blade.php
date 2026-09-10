@extends('layouts.shop')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('shop.index') }}" class="text-purple fw-bold text-decoration-none" style="color: #7E22CE;"><i class="bi bi-house-door me-1"></i>Science Kits</a></li>
            @if ($product->category)
                <li class="breadcrumb-item"><a href="{{ route('shop.index', ['category' => $product->category->slug]) }}" class="text-purple fw-bold text-decoration-none" style="color: #7E22CE;">{{ $product->category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active text-muted fw-semibold" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>
</div>

<div class="row g-4">
    <!-- Media Carousel Column -->
    <div class="col-lg-6">
        <div class="sci-card p-3 h-100 d-flex flex-column justify-content-center">
            @if ($product->images && $product->images->isNotEmpty())
                <div id="productCarousel" class="carousel slide rounded-3 overflow-hidden" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($product->images as $index => $image)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" style="background: #F3E8FF; min-height: 380px;">
                                <img src="{{ Storage::url($image->path) }}" class="d-block w-100" style="max-height: 440px; object-fit: contain;" alt="{{ $product->name }}">
                            </div>
                        @endforeach
                    </div>
                    @if ($product->images->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon p-3 rounded-circle" style="background-color: rgba(126, 34, 206, 0.6);" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon p-3 rounded-circle" style="background-color: rgba(126, 34, 206, 0.6);" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center py-5" style="background: #F8F5FF; border-radius: 12px;">
                    <i class="bi bi-stars fs-1 mb-3 d-block" style="color: #7E22CE;"></i>
                    <h5 class="fw-bold" style="color: #1E1B4B;">Kids Science Experiment Kit</h5>
                    <p class="text-muted small mb-0">High-Resolution Visual Archive in Progress</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Product Details Column -->
    <div class="col-lg-6">
        <div class="sci-glass-panel p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <div class="d-flex gap-2 mb-2 flex-wrap">
                        <span class="sci-badge sci-badge-purple">
                            <i class="bi bi-tag-fill"></i> {{ $product->category->name ?? 'STEM Experiment' }}
                        </span>
                        <span class="sci-badge sci-badge-pink">
                            <i class="bi bi-shield-check"></i> 100% Non-Toxic
                        </span>
                        <span class="sci-badge sci-badge-cyan">
                            <i class="bi bi-stars"></i> Ages 6+
                        </span>
                    </div>
                    <h2 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">{{ $product->name }}</h2>
                    <div class="small" style="color: #64748B;">
                        Kit Code / SKU: <code class="p-1 rounded fw-bold" style="background: #F1F5F9; color: #475569;">{{ $product->sku }}</code>
                    </div>
                </div>
            </div>

            <div class="my-3 py-3 border-top border-bottom" style="border-color: #EDE9FE !important;">
                <div class="d-flex align-items-baseline gap-3 flex-wrap">
                    <span class="sci-price fs-2">RM {{ number_format($product->price, 2) }}</span>
                    @php $qty = $product->inventory->quantity_on_hand ?? 0; @endphp
                    @if ($qty <= 0)
                        <span class="sci-badge sci-badge-danger"><i class="bi bi-x-octagon"></i> Currently Out of Stock</span>
                    @else
                        <span class="sci-badge sci-badge-emerald"><i class="bi bi-check-circle"></i> In Stock ({{ $qty }} kits available)</span>
                    @endif
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <h6 class="sci-heading fw-bold small mb-2" style="color: #4C1D95;"><i class="bi bi-card-text me-1"></i>What's Inside & Experiment Guide:</h6>
                <div class="p-3 rounded-3 small" style="background: #F8F5FF; border: 1px solid #E9D5FF; line-height: 1.6; color: #334155; font-weight: 500;">
                    {{ $product->description ?? 'Complete hands-on STEM experiment kit including all safety tools, chemical reactives, measuring test tubes, and easy-to-follow visual instruction cards.' }}
                </div>
            </div>

            <!-- Add to Cart / Wishlist Actions -->
            <div class="mt-auto">
                @auth
                    @if ($qty > 0)
                        <form method="POST" action="{{ route('cart.store') }}" class="row g-2 align-items-center mb-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div class="col-4">
                                <label for="quantity" class="sci-form-label small">Quantity:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-2" style="border-color: #E9D5FF; color: #7E22CE;"><i class="bi bi-box2"></i></span>
                                    <input type="number" name="quantity" id="quantity" class="sci-form-control form-control" value="1" min="1" max="{{ $qty }}">
                                </div>
                            </div>
                            <div class="col-8 align-self-end">
                                <button type="submit" class="btn btn-sci-primary w-100 py-2">
                                    <i class="bi bi-cart3 fs-5"></i>
                                    <span>Add to Cart</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('wishlist.store', $product) }}" class="flex-grow-1">
                            @csrf
                            <button type="submit" class="btn btn-sci-outline btn-sm w-100">
                                <i class="bi bi-heart-fill me-1 text-danger"></i>Save to Dream Wishlist
                            </button>
                        </form>
                    </div>
                @else
                    <div class="sci-alert sci-alert-info mb-0">
                        <i class="bi bi-stars fs-4 text-warning"></i>
                        <div>
                            Please <a href="{{ route('login') }}" class="fw-bold text-decoration-underline" style="color: #7E22CE;">sign in</a> or <a href="{{ route('register') }}" class="fw-bold text-decoration-underline" style="color: #7E22CE;">register</a> to add this kit to your explorer box.
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</div>

<!-- Related Products Section -->
@if ($related && $related->isNotEmpty())
    <div class="mt-5 pt-4 border-top" style="border-color: #E9D5FF !important;">
        <h4 class="sci-heading mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-stars text-warning"></i>
            <span>More Fun Science Kits You Might Love</span>
        </h4>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
            @foreach ($related as $rel)
                <div class="col">
                    <div class="sci-card h-100 p-3 d-flex flex-column">
                        <div class="rounded-3 overflow-hidden mb-3" style="height: 150px; background: #F3E8FF;">
                            @if ($rel->primaryImage)
                                <img src="{{ Storage::url($rel->primaryImage->path) }}" class="w-100 h-100 object-fit-cover" alt="{{ $rel->name }}">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-stars fs-3" style="color: #7E22CE;"></i>
                                </div>
                            @endif
                        </div>
                        <h6 class="sci-heading fw-bold mb-1">
                            <a href="{{ route('shop.show', $rel) }}" class="text-dark text-decoration-none hover-purple">
                                {{ $rel->name }}
                            </a>
                        </h6>
                        <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                            <span class="sci-price small">RM {{ number_format($rel->price, 2) }}</span>
                            <a href="{{ route('shop.show', $rel) }}" class="btn btn-sci-outline btn-sm py-1 px-2" style="font-size: 0.78rem;">
                                View Kit
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
