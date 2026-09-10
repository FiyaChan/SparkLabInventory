@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sales & Revenue Analytics</h4>
        <p class="text-muted small mb-0">Financial metrics from <strong>{{ $from->format('d M Y') }}</strong> to <strong>{{ $to->format('d M Y') }}</strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.sales.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
        <a href="{{ route('admin.reports.sales.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
        </a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Reports Menu
        </a>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Start Date</label>
                <input type="date" name="from" class="form-control" value="{{ request('from', $from->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">End Date</label>
                <input type="date" name="to" class="form-control" value="{{ request('to', $to->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-admin-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Update Report Range
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Total Completed Orders</div>
            <div class="admin-stat-value text-primary">{{ $summary['total_orders'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Period Gross Revenue</div>
            <div class="admin-stat-value text-success">RM {{ number_format($summary['total_revenue'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Average Basket Size (AOV)</div>
            <div class="admin-stat-value text-dark">RM {{ number_format($summary['average_order_value'], 2) }}</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <i class="bi bi-table text-primary me-2"></i>Orders in Selected Period
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th class="text-center">Line Items</th>
                    <th class="text-end">Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="fw-bold">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td>{{ $order->user->name ?? 'N/A' }}</td>
                        <td class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</td>
                        <td class="text-center">{{ $order->items->count() }}</td>
                        <td class="text-end fw-bold">RM {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <span class="admin-badge success">{{ ucfirst($order->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No order activity recorded in this time window.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
