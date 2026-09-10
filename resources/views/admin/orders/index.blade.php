@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Orders</h4>
        <p class="text-muted small mb-0">Review pending orders, update fulfillment workflow states, and manage payment receipts.</p>
    </div>
</div>

<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search order number or customer..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">All Fulfillment Statuses</option>
                    @foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-admin-primary flex-grow-1">Filter</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-admin-outline">Reset</a>
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
                    <th>Order Ref #</th>
                    <th>Customer</th>
                    <th>Date Placed</th>
                    <th>Total Amount</th>
                    <th>Payment Method & Status</th>
                    <th>Fulfillment Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $badgeClass = match($order->status) {
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            'shipped' => 'info',
                            'processing' => 'primary',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none text-primary">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $order->user->name ?? 'Guest / Deleted' }}</div>
                            <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                        </td>
                        <td class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</td>
                        <td class="fw-bold">RM {{ number_format($order->total_amount, 2) }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <span class="admin-badge secondary" style="font-size: 0.7rem;">
                                    {{ $order->payment->method === 'cod' ? 'COD' : 'ONLINE' }}
                                </span>
                                <span class="admin-badge {{ ($order->payment->status ?? '') === 'paid' ? 'success' : 'warning' }}" style="font-size: 0.7rem;">
                                    {{ ucfirst($order->payment->status ?? 'pending') }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="admin-badge {{ $badgeClass }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-admin-primary">
                                <i class="bi bi-pencil-square me-1"></i>Process Order
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-receipt-cutoff fs-2 d-block mb-2 text-muted"></i>
                            No orders found matching filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
