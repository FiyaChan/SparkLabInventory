@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory Valuation & Safety Audit</h4>
        <p class="text-muted small mb-0">Current snapshot of inventory values, quantities on hand, and reorder alerts.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.inventory.export.pdf') }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('admin.reports.inventory.export.excel') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
        </a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Reports Menu
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Active Tracked SKUs</div>
            <div class="admin-stat-value text-primary">{{ $summary['total_sku'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Total Stock Retail Valuation</div>
            <div class="admin-stat-value text-success">RM {{ number_format($summary['total_stock_value'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Low Stock Triggered</div>
            <div class="admin-stat-value text-danger">{{ $summary['low_stock_count'] }}</div>
        </div>
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
                    <th class="text-center">Stock on Hand</th>
                    <th class="text-center">Reorder Point</th>
                    <th class="text-end">Inventory Value</th>
                    <th>Stock Health</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    @php
                        $qty = $product->inventory->quantity_on_hand ?? 0;
                        $reorder = $product->inventory->reorder_level ?? 0;
                        $isLow = $qty <= $reorder;
                    @endphp
                    <tr>
                        <td><code>{{ $product->sku }}</code></td>
                        <td class="fw-semibold">{{ $product->name }}</td>
                        <td><span class="admin-badge secondary">{{ $product->category->name ?? '-' }}</span></td>
                        <td class="text-center fw-bold">{{ $qty }}</td>
                        <td class="text-center text-muted">{{ $reorder }}</td>
                        <td class="text-end fw-semibold">RM {{ number_format($qty * $product->price, 2) }}</td>
                        <td>
                            <span class="admin-badge {{ $isLow ? 'danger' : 'success' }}">
                                <i class="bi {{ $isLow ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }}"></i>
                                {{ $isLow ? 'Restock Needed' : 'Healthy' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
