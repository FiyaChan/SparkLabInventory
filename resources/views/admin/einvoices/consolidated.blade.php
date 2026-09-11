@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.einvoices.index') }}" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0">Consolidated e-Invoice Generator (Invois Disatukan)</h4>
            </div>
            <p class="text-muted small mb-0 ps-4 ms-2">Batch aggregate B2C walk-in retail & general customer sales into a single consolidated e-Invoice compliant with LHDN monthly filing rules.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <!-- Generator Form Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3-range text-primary"></i>
                    <span>Batch Date Range Selection</span>
                </h5>

                <form method="POST" action="{{ route('admin.einvoices.consolidated.generate') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="start_date" class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="{{ old('start_date', $startOfMonth->format('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-4">
                        <label for="end_date" class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="{{ old('end_date', $endOfMonth->format('Y-m-d')) }}" required>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Eligible Sales Orders:</span>
                            <span class="fw-bold text-dark">{{ $eligibleOrders->count() }} Orders</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total Batch Value:</span>
                            <span class="fw-bold text-success">RM {{ number_format($eligibleOrders->sum('total_amount'), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">LHDN Buyer Classification:</span>
                            <span class="badge bg-secondary-subtle text-secondary border">EI00000000020 (General Public)</span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-sm py-2 fw-semibold" {{ $eligibleOrders->isEmpty() ? 'disabled' : '' }}>
                            <i class="bi bi-patch-check-fill me-1"></i>Generate Consolidated e-Invoice
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guideline Alert Box -->
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-info-subtle text-info-emphasis">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                    <div class="small">
                        <strong>LHDN Guideline Note:</strong><br>
                        Under Inland Revenue Board of Malaysia regulations, suppliers are permitted to aggregate B2C transactions where buyers did not request individual e-invoices into a Consolidated e-Invoice submitted within 7 calendar days after the end of the month.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Eligible Orders Preview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-list-check text-primary"></i>
                        <span>Eligible Orders in Period ({{ $eligibleOrders->count() }})</span>
                    </h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        RM {{ number_format($eligibleOrders->sum('total_amount'), 2) }}
                    </span>
                </div>

                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Order Number</th>
                                <th>Date / Channel</th>
                                <th>Customer</th>
                                <th class="text-end pe-3">Amount (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eligibleOrders as $order)
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">
                                        {{ $order->order_number }}
                                    </td>
                                    <td>
                                        <div>{{ $order->created_at->format('d M Y, h:i A') }}</div>
                                        <span class="badge {{ $order->source === 'pos' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary' }}" style="font-size: 0.7rem;">
                                            {{ strtoupper($order->source) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ $order->shipping_name }}</div>
                                        <small class="text-muted">{{ $order->shipping_phone }}</small>
                                    </td>
                                    <td class="text-end pe-3 fw-semibold">
                                        RM {{ number_format($order->total_amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-check2-all fs-1 d-block mb-2 text-success"></i>
                                        <div>All completed transactions in this date range already have e-Invoices!</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
