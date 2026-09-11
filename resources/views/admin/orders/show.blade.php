@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Order #{{ $order->order_number }}</h4>
        <p class="text-muted small mb-0">Placed on {{ $order->created_at->format('d M Y, H:i') }} &bull; Complete order invoice and fulfillment details.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('orders.invoice', $order) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF Invoice
        </a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Back to Orders
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Items Table --}}
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <i class="bi bi-box-seam text-primary me-2"></i>Ordered Items
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->product->name ?? $item->product_name ?? 'Deleted Item' }}</div>
                                    @if($item->product)
                                        <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                    @endif
                                </td>
                                <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                <td class="text-end">RM {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">RM {{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="3" class="text-end fw-bold">Grand Total Amount:</td>
                            <td class="text-end fw-bold text-primary fs-5">RM {{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        {{-- Status Update Card --}}
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <i class="bi bi-arrow-repeat text-primary me-2"></i>Update Order Status
            </div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label">Fulfillment Status</label>
                        <select name="status" class="form-select mb-3">
                            @foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled'] as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-admin-primary w-100">
                        <i class="bi bi-check2 me-1"></i>Commit Status Update
                    </button>
                </form>
            </div>
        </div>

        {{-- Customer Info Card --}}
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <i class="bi bi-person text-info me-2"></i>Customer & Shipping Details
            </div>
            <div class="admin-card-body">
                <div class="fw-bold mb-1">{{ $order->shipping_name }}</div>
                <div class="text-muted small mb-2"><i class="bi bi-telephone me-1"></i>{{ $order->shipping_phone }}</div>
                <div class="p-2 bg-light rounded small text-secondary">
                    <strong>Shipping Address:</strong><br>
                    {!! nl2br(e($order->shipping_address)) !!}
                </div>
            </div>
        </div>

        {{-- Payment Summary Card --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <i class="bi bi-credit-card text-success me-2"></i>Payment Details
            </div>
            <div class="admin-card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Method:</span>
                    <span class="fw-semibold small">
                        @if (($order->payment->method ?? '') === 'toyyibpay')
                            ToyyibPay (FPX Online Banking)
                        @elseif (($order->payment->method ?? '') === 'cod')
                            Cash on Delivery (COD)
                        @elseif (($order->payment->method ?? '') === 'online_simulation')
                            Online Simulation
                        @elseif (($order->payment->method ?? '') === 'cash')
                            Cash (POS)
                        @elseif (($order->payment->method ?? '') === 'qr')
                            QR Payment (POS)
                        @else
                            {{ ucfirst($order->payment->method ?? 'N/A') }}
                        @endif
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Payment Status:</span>
                    <span class="admin-badge {{ ($order->payment->status ?? '') === 'paid' ? 'success' : (($order->payment->status ?? '') === 'failed' ? 'danger' : 'warning') }}">
                        {{ ucfirst($order->payment->status ?? 'pending') }}
                    </span>
                </div>
                @if($order->payment?->transaction_ref ?? $order->payment?->transaction_reference ?? null)
                    <div class="pt-2 border-top">
                        <span class="text-muted small d-block">Transaction / Bill Ref:</span>
                        <code class="small text-dark">{{ $order->payment->transaction_ref ?? $order->payment->transaction_reference }}</code>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
