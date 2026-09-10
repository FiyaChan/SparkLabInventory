@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Executive Dashboard</h4>
        <p class="text-muted small mb-0">Live overview of sales performance, inventory status, and system audit alerts.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.sales') }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>Sales Report
        </a>
    </div>
</div>

{{-- Sales Statistics Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Sales Today</div>
                    <div class="admin-stat-value">RM {{ number_format($salesStats['today'], 2) }}</div>
                </div>
                <div class="admin-stat-icon primary">
                    <i class="bi bi-calendar2-day"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <span class="text-success fw-semibold"><i class="bi bi-graph-up me-1"></i>Realtime</span> vs yesterday
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Sales This Month</div>
                    <div class="admin-stat-value">RM {{ number_format($salesStats['this_month'], 2) }}</div>
                </div>
                <div class="admin-stat-icon success">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <span class="text-primary fw-semibold">Current Billing Period</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">All-Time Revenue</div>
                    <div class="admin-stat-value">RM {{ number_format($salesStats['all_time'], 2) }}</div>
                </div>
                <div class="admin-stat-icon purple">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <span class="text-muted fw-semibold">Total Gross Sales</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Pending Orders</div>
                    <div class="admin-stat-value text-warning">{{ $salesStats['pending_orders'] }}</div>
                </div>
                <div class="admin-stat-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">
                <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="text-warning text-decoration-none fw-semibold">
                    Requires Fulfillment &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Inventory Overview Cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Active SKUs</div>
                    <div class="admin-stat-value">{{ $inventoryOverview['total_products'] }}</div>
                </div>
                <div class="admin-stat-icon primary">
                    <i class="bi bi-tags"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Active in catalog</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Units in Warehouse</div>
                    <div class="admin-stat-value">{{ number_format($inventoryOverview['total_units']) }}</div>
                </div>
                <div class="admin-stat-icon success">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Total counted stock</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Low Stock Alerts</div>
                    <div class="admin-stat-value text-danger">{{ $inventoryOverview['low_stock_count'] }}</div>
                </div>
                <div class="admin-stat-icon danger">
                    <i class="bi bi-exclamation-octagon"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Below reorder threshold</div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="admin-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="admin-stat-label">Out of Stock</div>
                    <div class="admin-stat-value text-danger">{{ $inventoryOverview['out_of_stock_count'] }}</div>
                </div>
                <div class="admin-stat-icon danger">
                    <i class="bi bi-x-octagon-fill"></i>
                </div>
            </div>
            <div class="small text-muted mt-2">Depleted inventory items</div>
        </div>
    </div>
</div>

{{-- Charts & Security Alerts --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-primary"></i>
                    <span>Sales Velocity (Last 7 Days)</span>
                </div>
                <span class="badge bg-primary-subtle text-primary">Daily Gross (RM)</span>
            </div>
            <div class="admin-card-body">
                <div style="height: 260px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Security Alerts audit trail widget --}}
    <div class="col-lg-4">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-exclamation text-danger"></i>
                    <span>Security & Audit Alerts</span>
                </div>
                @can('activity-log.view')
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-link p-0 text-decoration-none">View All</a>
                @endcan
            </div>
            <div class="admin-card-body p-0" style="max-height: 280px; overflow-y: auto;">
                @forelse ($securityAlerts as $alert)
                    <div class="p-3 border-bottom d-flex align-items-start gap-2">
                        <span class="admin-badge danger mt-1" style="font-size: 0.65rem;">
                            {{ str_replace('.', ' ', $alert->action) }}
                        </span>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate small">{{ $alert->user->email ?? 'Guest / Unknown' }}</div>
                            <div class="text-muted small" style="font-size: 0.75rem;">
                                IP: <code>{{ $alert->ip_address }}</code> &bull; {{ $alert->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted small">
                        <i class="bi bi-shield-check text-success fs-3 d-block mb-2"></i>
                        No critical security alerts detected recently.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Tables: Low Stock and Recent Orders --}}
<div class="row g-4">
    {{-- Low Stock table --}}
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <span>Inventory Reorder Action List</span>
                </div>
                <a href="{{ route('admin.inventory.index') }}" class="small text-decoration-none">Manage All</a>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Reorder Level</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockProducts as $product)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $product->name }}</div>
                                    <small class="text-muted">SKU: {{ $product->sku }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="admin-badge {{ ($product->inventory->quantity_on_hand ?? 0) <= 0 ? 'danger' : 'warning' }}">
                                        {{ $product->inventory->quantity_on_hand ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center text-muted">
                                    {{ $product->inventory->reorder_level ?? 10 }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.inventory.show', $product) }}" class="btn btn-sm btn-outline-primary">
                                        Restock
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-check-all text-success fs-4 d-block mb-1"></i>
                                    All inventory items are currently above safety stock thresholds.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Orders table --}}
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt text-primary"></i>
                    <span>Recent Customer Orders</span>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="small text-decoration-none">All Orders</a>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            @php
                                $statusClass = match($order->status) {
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    'shipped' => 'info',
                                    'processing' => 'primary',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 140px;">
                                        {{ $order->user->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="fw-semibold">RM {{ number_format($order->total_amount, 2) }}</td>
                                <td>
                                    <span class="admin-badge {{ $statusClass }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No recent orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    const salesData = @json($salesChartData);
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Gradient fill for sleek modern chart
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: [{
                label: 'Revenue (RM)',
                data: salesData.values,
                borderColor: '#2563eb',
                borderWidth: 2.5,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                    callbacks: {
                        label: function(context) {
                            return ' Sales: RM ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function(value) { return 'RM ' + value; }
                    }
                }
            }
        }
    });
</script>
@endpush
@endsection
