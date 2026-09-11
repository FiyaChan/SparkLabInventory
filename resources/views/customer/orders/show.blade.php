@extends('layouts.shop')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="sci-heading fw-bold mb-1">
            <i class="bi bi-box2-heart-fill me-2" style="color: #7E22CE;"></i>Order Details #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
        </h4>
        <p class="text-muted small mb-0">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('orders.invoice', $order) }}" class="btn btn-sci-primary btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Download PDF Invoice
        </a>
        <a href="{{ route('orders.index') }}" class="btn btn-sci-outline btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Orders
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="sci-card p-4 mb-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-box-seam me-2" style="color: #7E22CE;"></i>Ordered Science Kits</h5>
            <div class="table-responsive">
                <table class="table sci-table align-middle">
                    <thead>
                        <tr>
                            <th>Kit Description</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">
                                        @if ($item->product)
                                            <a href="{{ route('shop.show', $item->product) }}" class="text-dark text-decoration-none hover-purple">
                                                {{ $item->product->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">{{ $item->product_name ?? 'Science Kit' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end text-muted">RM {{ number_format($item->price, 2) }}</td>
                                <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                <td class="text-end sci-price">RM {{ number_format($item->quantity * $item->price, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="text-end fw-bold fs-6" style="color: #1E1B4B;">Total Order Value:</td>
                            <td class="text-end sci-price fs-4">RM {{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

        <!-- LHDN e-Invoice Card -->
        @if($order->eInvoice)
            <div class="sci-card p-3 mb-4 border border-success-subtle bg-white shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small text-success d-flex align-items-center gap-1">
                        <i class="bi bi-patch-check-fill text-success"></i>
                        <span>LHDN e-Invoice Validated</span>
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">MyInvois UBL 2.1</span>
                </div>
                <div class="small text-muted mb-1">Invoice No: <strong class="text-dark">{{ $order->eInvoice->invoice_number }}</strong></div>
                <div class="small text-muted mb-2 text-truncate">UUID: <code class="text-primary" style="font-size: 0.75rem;">{{ $order->eInvoice->irbm_unique_id }}</code></div>
                <div class="d-flex gap-2">
                    <a href="{{ route('orders.invoice', $order) }}" class="btn btn-sm btn-outline-success w-50 py-1">
                        <i class="bi bi-file-earmark-pdf me-1"></i>e-Invoice PDF
                    </a>
                    <a href="{{ route('einvoice.verify', $order->eInvoice->irbm_unique_id) }}" target="_blank" class="btn btn-sm btn-outline-primary w-50 py-1">
                        <i class="bi bi-qr-code-scan me-1"></i>Verify QR
                    </a>
                </div>
            </div>
        @endif

        <!-- Delivery details -->
        <div class="sci-glass-panel p-4 mb-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-geo-alt me-2" style="color: #7E22CE;"></i>Delivery Address</h5>
            <div class="fw-bold fs-6 mb-1" style="color: #1E1B4B;">{{ $order->shipping_name }}</div>
            <div class="text-muted small mb-3"><i class="bi bi-telephone me-1"></i>{{ $order->shipping_phone }}</div>
            <div class="p-3 rounded-3 small" style="background: #F8F5FF; border: 1px solid #E9D5FF; color: #334155; font-weight: 500;">
                <strong class="text-dark d-block mb-1">Address:</strong>
                {!! nl2br(e($order->shipping_address)) !!}
            </div>
        </div>

        <!-- Payment details -->
        <div class="sci-glass-panel p-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-credit-card me-2" style="color: #7E22CE;"></i>Payment Summary</h5>
            <div class="d-flex justify-content-between mb-2 small fw-semibold">
                <span class="text-muted">Payment Mode:</span>
                <span class="text-dark">
                    @if (($order->payment->method ?? '') === 'toyyibpay')
                        <span class="fw-bold" style="color: #7E22CE;"><i class="bi bi-bank me-1"></i>ToyyibPay (FPX Banking)</span>
                    @elseif (($order->payment->method ?? '') === 'cod')
                        Cash on Delivery (COD)
                    @elseif (($order->payment->method ?? '') === 'online_simulation')
                        Online Simulation
                    @else
                        {{ ucfirst($order->payment->method ?? 'N/A') }}
                    @endif
                </span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 small fw-semibold">
                <span class="text-muted">Fulfillment:</span>
                @switch($order->status)
                    @case('pending')
                        <span class="sci-badge sci-badge-amber">Packing Kit (Pending)</span>
                        @break
                    @case('completed')
                        <span class="sci-badge sci-badge-emerald">Delivered</span>
                        @break
                    @case('cancelled')
                        <span class="sci-badge sci-badge-danger">Cancelled</span>
                        @break
                    @default
                        <span class="sci-badge sci-badge-purple">{{ ucfirst($order->status) }}</span>
                @endswitch
            </div>
            @if ($order->payment)
                <div class="d-flex justify-content-between align-items-center mb-2 small fw-semibold">
                    <span class="text-muted">Payment Status:</span>
                    <span class="sci-badge {{ $order->payment->status === 'paid' ? 'sci-badge-emerald' : ($order->payment->status === 'failed' ? 'sci-badge-danger' : 'sci-badge-amber') }}">
                        {{ ucfirst($order->payment->status) }}
                    </span>
                </div>
                @if ($order->payment->transaction_ref ?? $order->payment->transaction_reference ?? null)
                    <div class="pt-2 border-top small" style="border-color: #EDE9FE !important;">
                        <span class="text-muted d-block">Transaction / Bill Ref:</span>
                        <code class="p-1 rounded fw-bold" style="background: #F1F5F9; color: #475569;">{{ $order->payment->transaction_ref ?? $order->payment->transaction_reference }}</code>
                    </div>
                @endif

                @if (($order->payment->method ?? '') === 'toyyibpay' && $order->payment->status !== 'paid' && $order->status !== 'cancelled')
                    <div class="pt-3 mt-3 border-top" style="border-color: #EDE9FE !important;">
                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-sci-primary w-100 py-2">
                            <i class="bi bi-wallet2 me-1"></i>Pay with ToyyibPay FPX
                        </a>
                        <small class="text-muted text-center d-block mt-1" style="font-size: 0.75rem;">Instant FPX Online Banking</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
