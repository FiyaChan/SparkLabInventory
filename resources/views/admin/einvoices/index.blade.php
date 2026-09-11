@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-ruled-fill text-primary"></i>
                <span>LHDN e-Invoicing Management (MyInvois)</span>
            </h4>
            <p class="text-muted small mb-0">Monitor, generate, and inspect Malaysian Inland Revenue Board (IRBM / LHDN) e-Invoices across POS and E-Commerce.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.einvoices.consolidated') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-collection-fill"></i>
                <span>Consolidated e-Invoice</span>
            </a>
            @can('user.manage')
                <a href="{{ route('admin.einvoices.settings') }}" class="btn btn-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-gear-fill"></i>
                    <span>Tax Settings</span>
                </a>
            @endcan
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total e-Invoices</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['total_count']) }}</div>
                        <div class="small text-success mt-1"><i class="bi bi-patch-check me-1"></i>{{ $stats['valid_count'] }} Validated</div>
                    </div>
                    <div class="p-3 bg-primary-subtle text-primary rounded-3">
                        <i class="bi bi-file-earmark-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">POS Cashier Invoices</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['pos_count']) }}</div>
                        <div class="small text-muted mt-1">Walk-in & Retail Sales</div>
                    </div>
                    <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-3">
                        <i class="bi bi-shop-window fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">E-Commerce Invoices</div>
                        <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['ecommerce_count']) }}</div>
                        <div class="small text-muted mt-1">ToyyibPay & Online Orders</div>
                    </div>
                    <div class="p-3 bg-info-subtle text-info rounded-3">
                        <i class="bi bi-globe2 fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Validated Value</div>
                        <div class="fs-4 fw-bold text-success mt-1">RM {{ number_format($stats['total_value'], 2) }}</div>
                        <div class="small text-muted mt-1">{{ $stats['consolidated_count'] }} Consolidated Batches</div>
                    </div>
                    <div class="p-3 bg-success-subtle text-success rounded-3">
                        <i class="bi bi-cash-coin fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.einvoices.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search Invoice No, UUID, Buyer Name / TIN..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 col-6">
                    <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" @selected(request('source') === 'all' || !request('source'))>All Channels</option>
                        <option value="pos" @selected(request('source') === 'pos')>POS Terminal</option>
                        <option value="ecommerce" @selected(request('source') === 'ecommerce')>E-Commerce</option>
                        <option value="manual" @selected(request('source') === 'manual')>Consolidated</option>
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" @selected(request('status') === 'all' || !request('status'))>All Statuses</option>
                        <option value="valid" @selected(request('status') === 'valid')>Valid / Disahkan</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2 col-6">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" @selected(request('type') === 'all' || !request('type'))>All Types</option>
                        <option value="01" @selected(request('type') === '01')>01 - Standard Invoice</option>
                        <option value="consolidated" @selected(request('type') === 'consolidated')>Consolidated</option>
                    </select>
                </div>

                <div class="col-md-2 col-6 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    @if(request()->hasAny(['search', 'source', 'status', 'type']))
                        <a href="{{ route('admin.einvoices.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- e-Invoices Data Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.04em;">
                    <tr>
                        <th class="ps-3 py-3">Invoice Details</th>
                        <th>LHDN Unique UUID</th>
                        <th>Buyer (Customer)</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th class="text-end">Total (MYR)</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eInvoices as $inv)
                        <tr>
                            <td class="ps-3 py-3">
                                <div class="fw-bold text-dark">{{ $inv->invoice_number }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    {{ $inv->issued_at->format('d M Y, h:i A') }}
                                    @if($inv->order)
                                        &bull; <a href="{{ route('admin.orders.show', $inv->order_id) }}" class="text-primary text-decoration-none">#{{ $inv->order->order_number }}</a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <code class="text-primary fw-semibold" style="font-size: 0.78rem;">{{ Str::limit($inv->irbm_unique_id, 18) }}</code>
                                    <button class="btn btn-sm btn-link p-0 text-muted" onclick="navigator.clipboard.writeText('{{ $inv->irbm_unique_id }}')" title="Copy UUID">
                                        <i class="bi bi-copy" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    Type: {{ $inv->invoice_type === 'consolidated' ? 'Consolidated e-Invoice' : '01 (Standard)' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ Str::limit($inv->buyer_name, 22) }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    TIN: <span class="badge bg-light text-secondary border">{{ $inv->buyer_tin }}</span>
                                </div>
                            </td>
                            <td>
                                @if($inv->source === 'pos')
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-shop me-1"></i>POS Counter
                                    </span>
                                @elseif($inv->source === 'ecommerce')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-globe me-1"></i>E-Commerce
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <i class="bi bi-collection me-1"></i>Consolidated
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $inv->status_badge_class }} border" style="font-size: 0.75rem;">
                                    <i class="bi bi-patch-check-fill me-1"></i>{{ strtoupper($inv->status) }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                RM {{ number_format($inv->total_payable_amount, 2) }}
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm shadow-sm">
                                    <a href="{{ route('admin.einvoices.show', $inv) }}" class="btn btn-outline-secondary" title="Inspect UBL JSON & Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.einvoices.pdf', $inv) }}" class="btn btn-outline-secondary text-danger" title="Download PDF e-Invoice">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="{{ route('einvoice.verify', $inv->irbm_unique_id) }}" target="_blank" class="btn btn-outline-secondary text-primary" title="View Public QR Verification">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <div>No e-Invoices found matching your search criteria.</div>
                                <div class="small mt-1">e-Invoices are automatically generated when a POS sale or online order payment is completed.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($eInvoices->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $eInvoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
