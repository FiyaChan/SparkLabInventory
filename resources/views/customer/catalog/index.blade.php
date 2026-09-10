@extends('layouts.shop')

@section('content')
<div class="row g-4">
    <!-- Category Sidebar & Quick Filters -->
    <div class="col-lg-3">
        <div class="sci-card p-4 mb-4">
            <h5 class="sci-heading mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-compass-fill text-purple" style="color: #7E22CE;"></i>
                <span>Experiment Categories</span>
            </h5>
            
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('shop.index') }}" 
                   class="sci-category-pill {{ !request('category') ? 'active' : '' }}">
                    <i class="bi bi-stars"></i>
                    <span>All Science Kits</span>
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('shop.index', ['category' => $category->slug]) }}"
                       class="sci-category-pill {{ request('category') === $category->slug ? 'active' : '' }}">
                        <i class="bi bi-flask-fill" style="color: #EC4899;"></i>
                        <span>{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Kids STEM Tip Card -->
        <div class="p-4 rounded-4 d-none d-lg-block" style="background: linear-gradient(135deg, #F3E8FF 0%, #EDE9FE 100%); border: 2px dashed #D8B4FE;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-award-fill text-warning fs-4"></i>
                <span class="fw-bold fs-6" style="color: #4C1D95;">100% Kid-Safe Certified</span>
            </div>
            <p class="small mb-0" style="color: #581C87; font-weight: 500; line-height: 1.5;">
                All experiment kits contain non-toxic materials, safety goggles, and colorful step-by-step comic guidebooks!
            </p>
        </div>
    </div>

    <!-- Main Product Catalog Area -->
    <div class="col-lg-9">
        <!-- Filter & Search Toolbar -->
        <div class="sci-card p-3 mb-4">
            <form method="GET" class="row g-2 align-items-center">
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="col-md-6">
                    <div class="position-relative">
                        <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9333EA; font-size: 1rem; pointer-events: none; z-index: 4;"></i>
                        <input type="text" name="search" class="sci-form-control form-control" style="padding-left: 2.75rem !important;"
                               placeholder="Search kit name, experiment type, or SKU..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="sort" class="sci-form-select form-select">
                        <option value="">Sort: Featured / Newest</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Lowest to Highest</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: Highest to Lowest</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-sci-primary">
                        <span>Search</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Product Cards Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
            @forelse ($products as $product)
                @php $qty = $product->inventory->quantity_on_hand ?? 0; @endphp
                <div class="col">
                    <div class="sci-card h-100 d-flex flex-column">
                        <div class="position-relative overflow-hidden d-flex align-items-center justify-content-center" style="height: 200px; background: #FAF5FF;">
                            @if ($product->primaryImage)
                                <img src="{{ Storage::url($product->primaryImage->path) }}"
                                     class="w-100 h-100 object-fit-contain p-2"
                                     alt="{{ $product->name }}">
                            @else
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                    <i class="bi bi-stars fs-1 mb-1" style="color: #9333EA;"></i>
                                    <span class="small fw-bold" style="color: #7E22CE;">Experiment Kit Box</span>
                                </div>
                            @endif
                            <div class="position-absolute top-0 end-0 p-2">
                                @if ($qty <= 0)
                                    <span class="sci-badge sci-badge-danger">Sold Out</span>
                                @else
                                    <span class="sci-badge sci-badge-purple">{{ $qty }} In Stock</span>
                                @endif
                            </div>
                            @if($product->category)
                                <div class="position-absolute bottom-0 start-0 p-2">
                                    <span class="sci-badge sci-badge-pink">{{ $product->category->name }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <h5 class="sci-heading fw-bold mb-1 fs-6" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6rem;">
                                <a href="{{ route('shop.show', $product) }}" class="text-dark text-decoration-none hover-purple">
                                    {{ $product->name }}
                                </a>
                            </h5>
                            <div class="small mb-3" style="color: #64748B;">
                                Kit Code: <code class="p-1 rounded fw-bold" style="background: #F1F5F9; color: #475569;">{{ $product->sku }}</code>
                            </div>

                            <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: #EDE9FE !important;">
                                <div>
                                    <span class="sci-price">RM {{ number_format($product->price, 2) }}</span>
                                </div>
                                <a href="{{ route('shop.show', $product) }}" class="btn btn-sci-outline btn-sm">
                                    <i class="bi bi-eye"></i> View Kit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="sci-glass-panel text-center py-5">
                        <i class="bi bi-box2-heart fs-1 d-block mb-3" style="color: #9333EA;"></i>
                        <h4 class="fw-bold" style="color: #1E1B4B;">No Science Kits Found</h4>
                        <p class="text-muted small mb-3">Try adjusting your search query or choosing another experiment category.</p>
                        <a href="{{ route('shop.index') }}" class="btn btn-sci-primary btn-sm">Reset Filters</a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if ($products->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
