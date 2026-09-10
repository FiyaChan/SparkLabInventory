@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Product Details</h4>
    <div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Back to Products</a>
        @can('update', $product)
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary btn-sm">Edit Product</a>
        @endcan
    </div>
</div>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Product Images</h5>
                @if ($product->images->count() > 0)
                    <div class="row g-2">
                        @foreach ($product->images as $image)
                            <div class="col-6">
                                <img src="{{ asset('storage/' . $image->path) }}" class="img-fluid rounded border" alt="{{ $product->name }}">
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 bg-light rounded text-muted">
                        No images uploaded for this product.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ $product->name }}</h5>
                <p class="text-muted small">SKU: <code>{{ $product->sku }}</code> | Slug: <code>{{ $product->slug }}</code></p>
                
                <hr>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Category:</div>
                    <div class="col-8 fw-semibold">{{ $product->category->name ?? 'Uncategorized' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Selling Price:</div>
                    <div class="col-8 fw-semibold text-primary">RM {{ number_format($product->price, 2) }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Cost Price:</div>
                    <div class="col-8">{{ $product->cost_price ? 'RM ' . number_format($product->cost_price, 2) : '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Status:</div>
                    <div class="col-8">
                        <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Stock on Hand:</div>
                    <div class="col-8">
                        <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-success' }}">
                            {{ $product->inventory->quantity_on_hand ?? 0 }} units
                        </span>
                        <a href="{{ route('admin.inventory.show', $product) }}" class="ms-2 small text-decoration-none">Manage Stock &rarr;</a>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="text-muted small fw-bold">Description:</label>
                    <p class="mt-1 text-secondary">{{ $product->description ?: 'No description provided.' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
