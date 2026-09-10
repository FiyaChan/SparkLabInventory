@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Product Catalog</h4>
        <p class="text-muted small mb-0">Manage all registered products, SKU codes, pricing, and active catalog statuses.</p>
    </div>
    @can('create', App\Models\Product::class)
        <a href="{{ route('admin.products.create') }}" class="btn btn-admin-primary">
            <i class="bi bi-plus-lg me-1"></i>Add New Product
        </a>
    @endcan
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search by product name or SKU..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-admin-primary flex-grow-1">Filter</button>
                @if(request('search') || request('category_id'))
                    <a href="{{ route('admin.products.index') }}" class="btn btn-admin-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;">Media</th>
                    <th>Product Info</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th class="text-center">Stock Level</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            @if ($product->primaryImage)
                                <img src="{{ Storage::url($product->primaryImage->path) }}"
                                     class="rounded border" style="width: 48px; height: 48px; object-fit: cover;" alt="{{ $product->name }}">
                            @else
                                <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $product->name }}</div>
                            <small class="text-muted">SKU: <code class="text-dark">{{ $product->sku }}</code></small>
                        </td>
                        <td>
                            <span class="admin-badge secondary">{{ $product->category->name ?? 'Uncategorized' }}</span>
                        </td>
                        <td>
                            <div class="fw-bold">RM {{ number_format($product->price, 2) }}</div>
                            @if($product->cost_price)
                                <small class="text-muted">Cost: RM {{ number_format($product->cost_price, 2) }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $qty = $product->inventory->quantity_on_hand ?? 0; @endphp
                            <span class="admin-badge {{ $product->isLowStock() ? 'danger' : 'success' }}">
                                <i class="bi {{ $product->isLowStock() ? 'bi-exclamation-circle' : 'bi-check-circle' }}"></i>
                                {{ $qty }} units
                            </span>
                        </td>
                        <td>
                            @if ($product->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inventory.show', $product) }}" class="btn btn-outline-secondary" title="Stock Adjustment">
                                    <i class="bi bi-stack me-1"></i>Stock
                                </a>
                                @can('update', $product)
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary" title="Edit Details">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </a>
                                @endcan
                                @can('delete', $product)
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Product">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-box-seam fs-2 d-block mb-2 text-muted"></i>
                            No products found matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="p-3 border-top">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
