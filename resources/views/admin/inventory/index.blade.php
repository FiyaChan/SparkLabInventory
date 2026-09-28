@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory Ledger</h4>
        <p class="text-muted small mb-0">Monitor physical quantity on hand, reorder thresholds, and warehouse stock states.</p>
    </div>
    @can('create', App\Models\Product::class)
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-admin-primary">
            <i class="bi bi-plus-lg me-1"></i>New Product
        </a>
    @endcan
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="Search SKU or product name..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Stock Levels</option>
                    <option value="in_stock" @selected(request('filter') === 'in_stock')>In Stock (Healthy)</option>
                    <option value="low_stock" @selected(request('filter') === 'low_stock')>Low Stock (At / Below Reorder Point)</option>
                    <option value="out_of_stock" @selected(request('filter') === 'out_of_stock')>Out of Stock (0 units)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-admin-primary flex-grow-1">Filter</button>
                @if(request('search') || request('filter'))
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-admin-outline">Reset</a>
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
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th class="text-center">Units On Hand</th>
                    <th class="text-center">Reorder Point</th>
                    <th>Inventory Status</th>
                    <th class="text-end">Stock Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $qty = $product->inventory->quantity_on_hand ?? 0;
                        $reorder = $product->inventory->reorder_level ?? 0;
                        $isOutOfStock = $qty <= 0;
                        $isLowStock = !$isOutOfStock && $product->isLowStock();
                    @endphp
                    <tr>
                        <td><code class="fw-bold text-dark">{{ $product->sku }}</code></td>
                        <td>
                            <div class="fw-semibold">{{ $product->name }}</div>
                            <small class="text-muted">RM {{ number_format($product->price, 2) }}</small>
                        </td>
                        <td>
                            <span class="admin-badge secondary">{{ $product->category->name ?? 'Uncategorized' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold fs-6">{{ number_format($qty) }}</span>
                        </td>
                        <td class="text-center text-muted">
                            {{ $reorder }} units
                        </td>
                        <td>
                            @if ($isOutOfStock)
                                <span class="admin-badge danger"><i class="bi bi-x-circle-fill"></i> Out of Stock</span>
                            @elseif ($isLowStock)
                                <span class="admin-badge warning"><i class="bi bi-exclamation-triangle-fill"></i> Low Stock</span>
                            @else
                                <span class="admin-badge success"><i class="bi bi-check-circle-fill"></i> In Stock</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.inventory.show', $product) }}" class="btn btn-sm btn-admin-primary">
                                <i class="bi bi-arrow-left-right me-1"></i>Stock Movement
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-boxes fs-2 d-block mb-2 text-muted"></i>
                            No inventory items found.
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
