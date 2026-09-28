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
                                <img src="{{ $image->url }}"
                                     class="d-block w-100"
                                     style="max-height: 440px; object-fit: contain;"
                                     alt="{{ $product->name }}"
                                     onerror="this.onerror=null; this.src='{{ asset('images/product-placeholder.svg') }}';">
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
                        Kit Code / SKU: <code class="p-1 rounded fw-bold" id="displayedSku" style="background: #F1F5F9; color: #475569;">{{ $product->sku }}</code>
                    </div>
                </div>
            </div>

            <div class="my-3 py-3 border-top border-bottom" style="border-color: #EDE9FE !important;">
                <div class="d-flex align-items-baseline gap-3 flex-wrap">
                    <span class="sci-price fs-2" id="displayedPrice">{{ $product->formatted_price }}</span>
                    <span id="displayedStockBadge">
                        @php $totalStock = $product->total_stock; @endphp
                        @if ($totalStock <= 0)
                            <span class="sci-badge sci-badge-danger"><i class="bi bi-x-octagon"></i> Currently Out of Stock</span>
                        @else
                            <span class="sci-badge sci-badge-emerald"><i class="bi bi-check-circle"></i> In Stock ({{ $totalStock }} kits available)</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Product Variations Selection (If available) -->
            @if ($product->variations->isNotEmpty())
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="sci-form-label fw-bold mb-0" style="color: #4C1D95;">
                            <i class="bi bi-layers-fill me-1" style="color: #7E22CE;"></i>Select Edition / Variation:
                        </label>
                        <span class="text-muted small" id="selectedVariantLabel">Choose an option</span>
                    </div>

                    <div class="d-flex flex-column gap-2" id="variationTilesContainer">
                        @foreach ($product->variations as $variation)
                            <div class="variation-tile p-3 rounded-3 border d-flex justify-content-between align-items-center {{ $variation->stock <= 0 ? 'disabled opacity-50' : '' }}"
                                 data-id="{{ $variation->id }}"
                                 data-name="{{ $variation->name }}"
                                 data-price="RM {{ number_format($variation->price, 2) }}"
                                 data-sku="{{ $variation->sku ?? $product->sku }}"
                                 data-stock="{{ $variation->stock }}"
                                 onclick="{{ $variation->stock > 0 ? 'selectVariation(this)' : '' }}"
                                 style="background: #ffffff; border-color: #E9D5FF; cursor: {{ $variation->stock > 0 ? 'pointer' : 'not-allowed' }}; transition: all 0.2s ease;">
                                
                                <div class="d-flex align-items-center gap-2">
                                    <div class="variation-radio-indicator rounded-circle border d-flex align-items-center justify-content-center"
                                         style="width: 20px; height: 20px; border-color: #C084FC;">
                                        <div class="radio-dot rounded-circle d-none" style="width: 10px; height: 10px; background-color: #7E22CE;"></div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $variation->name }}</div>
                                        @if($variation->sku)
                                            <small class="text-muted" style="font-size: 0.75rem;">SKU: <code>{{ $variation->sku }}</code></small>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="fw-bold text-purple" style="color: #7E22CE;">RM {{ number_format($variation->price, 2) }}</div>
                                    @if ($variation->stock <= 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;">Out of Stock</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">{{ $variation->stock }} in stock</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

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
                    @if ($product->total_stock > 0)
                        <form method="POST" action="{{ route('cart.store') }}" class="row g-2 align-items-center mb-3" id="addToCartForm">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            @if ($product->variations->isNotEmpty())
                                <input type="hidden" name="variation_id" id="selectedVariationId" value="{{ $product->variations->where('stock', '>', 0)->first()?->id ?? $product->variations->first()?->id }}">
                            @endif
                            <div class="col-4">
                                <label for="quantity" class="sci-form-label small">Quantity:</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-2" style="border-color: #E9D5FF; color: #7E22CE;"><i class="bi bi-box2"></i></span>
                                    <input type="number" name="quantity" id="quantityInput" class="sci-form-control form-control" value="1" min="1" max="{{ $product->total_stock }}">
                                </div>
                            </div>
                            <div class="col-8 align-self-end">
                                <button type="submit" class="btn btn-sci-primary w-100 py-2" id="btnAddToCart">
                                    <i class="bi bi-cart3 fs-5"></i>
                                    <span id="addToCartText">Add to Cart</span>
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
                                <img src="{{ $rel->primaryImage->url }}"
                                     class="w-100 h-100 object-fit-contain p-2"
                                     alt="{{ $rel->name }}"
                                     onerror="this.onerror=null; this.src='{{ asset('images/product-placeholder.svg') }}';">
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
                            <span class="sci-price small">{{ $rel->formatted_price }}</span>
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

@push('scripts')
<script>
    function selectVariation(el) {
        document.querySelectorAll('.variation-tile').forEach(tile => {
            tile.style.borderColor = '#E9D5FF';
            tile.style.backgroundColor = '#FFFFFF';
            tile.style.boxShadow = 'none';
            const dot = tile.querySelector('.radio-dot');
            if (dot) dot.classList.add('d-none');
            const ind = tile.querySelector('.variation-radio-indicator');
            if (ind) ind.style.borderColor = '#C084FC';
        });

        el.style.borderColor = '#7E22CE';
        el.style.backgroundColor = '#FAF5FF';
        el.style.boxShadow = '0 4px 14px rgba(126, 34, 206, 0.12)';
        const activeDot = el.querySelector('.radio-dot');
        if (activeDot) activeDot.classList.remove('d-none');
        const activeInd = el.querySelector('.variation-radio-indicator');
        if (activeInd) activeInd.style.borderColor = '#7E22CE';

        const id = el.dataset.id;
        const name = el.dataset.name;
        const price = el.dataset.price;
        const sku = el.dataset.sku;
        const stock = parseInt(el.dataset.stock, 10);

        const varInput = document.getElementById('selectedVariationId');
        if (varInput) varInput.value = id;

        const priceEl = document.getElementById('displayedPrice');
        if (priceEl) priceEl.innerText = price;

        const skuEl = document.getElementById('displayedSku');
        if (skuEl) skuEl.innerText = sku;

        const labelEl = document.getElementById('selectedVariantLabel');
        if (labelEl) labelEl.innerHTML = `Selected: <strong style="color: #7E22CE;">${name}</strong>`;

        const stockBadge = document.getElementById('displayedStockBadge');
        const qtyInput = document.getElementById('quantityInput');
        const btnAdd = document.getElementById('btnAddToCart');
        const btnText = document.getElementById('addToCartText');

        if (stock <= 0) {
            if (stockBadge) stockBadge.innerHTML = '<span class="sci-badge sci-badge-danger"><i class="bi bi-x-octagon"></i> Out of Stock</span>';
            if (btnAdd) {
                btnAdd.disabled = true;
                if (btnText) btnText.innerText = 'Variation Sold Out';
            }
        } else {
            if (stockBadge) stockBadge.innerHTML = `<span class="sci-badge sci-badge-emerald"><i class="bi bi-check-circle"></i> In Stock (${stock} available)</span>`;
            if (qtyInput) {
                qtyInput.max = stock;
                if (parseInt(qtyInput.value, 10) > stock) qtyInput.value = 1;
            }
            if (btnAdd) {
                btnAdd.disabled = false;
                if (btnText) btnText.innerText = 'Add to Cart';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const firstAvailableTile = document.querySelector('.variation-tile:not(.disabled)');
        if (firstAvailableTile) {
            selectVariation(firstAvailableTile);
        }
    });
</script>
@endpush
