@extends('layouts.shop')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-rocket-takeoff-fill me-2" style="color: #7E22CE;"></i>My Science Orders & Kits</h4>
        <p class="text-muted small mb-0">Track delivery of your science experiment kits and download official invoices.</p>
    </div>
    <a href="{{ route('shop.index') }}" class="btn btn-sci-outline btn-sm">
        <i class="bi bi-stars me-1 text-warning"></i>Explore Kits
    </a>
</div>

@if ($orders->isEmpty())
    <div class="sci-glass-panel text-center py-5">
        <i class="bi bi-box2-heart fs-1 d-block mb-3" style="color: #7E22CE;"></i>
        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">No Science Kit Orders Yet</h4>
        <p class="text-muted small mb-4">You haven't ordered any experiment kits yet. Start your journey into fun STEM discovery!</p>
        <a href="{{ route('shop.index') }}" class="btn btn-sci-primary">
            <i class="bi bi-stars me-1"></i>Browse Kids Science Kits
        </a>
    </div>
@else
    <div class="sci-card">
        <div class="table-responsive">
            <table class="table sci-table align-middle">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Order Date</th>
                        <th>Delivery Status</th>
                        <th>Payment Mode</th>
                        <th>Total Amount</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="fw-bold text-decoration-none" style="color: #7E22CE;">
                                    #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td class="small text-muted">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                @switch($order->status)
                                    @case('pending')
                                        <span class="sci-badge sci-badge-amber">Packing Kit (Pending)</span>
                                        @break
                                    @case('completed')
                                        <span class="sci-badge sci-badge-emerald">Delivered & Complete</span>
                                        @break
                                    @case('cancelled')
                                        <span class="sci-badge sci-badge-danger">Cancelled</span>
                                        @break
                                    @default
                                        <span class="sci-badge sci-badge-purple">{{ ucfirst($order->status) }}</span>
                                @endswitch
                            </td>
                            <td class="small text-muted">
                                {{ ($order->payment->method ?? '') === 'cod' ? 'Cash on Delivery (COD)' : 'Online Banking Simulation' }}
                            </td>
                            <td class="sci-price">RM {{ number_format($order->total_amount, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sci-outline btn-sm">
                                    <i class="bi bi-receipt me-1"></i>View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="p-3 border-top d-flex justify-content-center" style="border-color: #EDE9FE !important;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
@endif
@endsection
