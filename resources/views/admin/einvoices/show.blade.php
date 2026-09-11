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
                <h4 class="fw-bold mb-0">e-Invoice #{{ $einvoice->invoice_number }}</h4>
                <span class="badge {{ $einvoice->status_badge_class }} border ms-2">
                    <i class="bi bi-patch-check-fill me-1"></i>{{ strtoupper($einvoice->status) }}
                </span>
            </div>
            <p class="text-muted small mb-0 ps-4 ms-2">
                Issued on {{ $einvoice->issued_at->format('d M Y, h:i:s A') }} &bull; 
                LHDN UUID: <code class="text-primary fw-semibold">{{ $einvoice->irbm_unique_id }}</code>
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.einvoices.pdf', $einvoice) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Download Official PDF</span>
            </a>
            <a href="{{ route('einvoice.verify', $einvoice->irbm_unique_id) }}" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-qr-code-scan"></i>
                <span>Public QR Verification</span>
            </a>
            @if($einvoice->order && $einvoice->source === 'pos')
                <a href="{{ route('admin.pos.receipt', $einvoice->order->id) }}" target="_blank" class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-receipt"></i>
                    <span>Thermal Receipt</span>
                </a>
            @endif
            @if($einvoice->status === 'valid')
                <form method="POST" action="{{ route('admin.einvoices.cancel', $einvoice) }}" onsubmit="return confirm('Are you sure you want to cancel this e-Invoice? This action is recorded in the audit trail.')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i>
                        <span>Cancel Invoice</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Details & Breakdown -->
        <div class="col-lg-7">
            <!-- Supplier & Buyer Tax Entities -->
            <div class="row g-3 mb-4">
                <!-- Supplier (Seller) -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center gap-2 text-primary fw-bold small mb-2">
                            <i class="bi bi-building"></i>
                            <span>SUPPLIER (PENJUAL)</span>
                        </div>
                        <div class="fw-bold fs-6 text-dark">{{ $einvoice->supplier_name }}</div>
                        <div class="text-muted small mt-1">TIN: <strong class="text-dark">{{ $einvoice->supplier_tin }}</strong></div>
                        <div class="text-muted small">SSM/BRN: {{ $einvoice->supplier_id_value }}</div>
                        <div class="text-muted small">MSIC Code: <strong>{{ $einvoice->supplier_msic_code }}</strong> ({{ $einvoice->supplier_msic_desc ?? 'Retail Goods' }})</div>
                        @if($einvoice->supplier_sst_no)
                            <div class="text-muted small">SST No: {{ $einvoice->supplier_sst_no }}</div>
                        @endif
                        <div class="text-muted small mt-2 pt-2 border-top">
                            {{ $einvoice->supplier_address }}<br>
                            Tel: {{ $einvoice->supplier_phone }} | {{ $einvoice->supplier_email }}
                        </div>
                    </div>
                </div>

                <!-- Buyer (Customer) -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center gap-2 text-primary fw-bold small mb-2">
                            <i class="bi bi-person-badge"></i>
                            <span>BUYER (PEMBELI)</span>
                        </div>
                        <div class="fw-bold fs-6 text-dark">{{ $einvoice->buyer_name }}</div>
                        <div class="text-muted small mt-1">TIN: <strong class="text-dark">{{ $einvoice->buyer_tin }}</strong></div>
                        <div class="text-muted small">{{ $einvoice->buyer_id_type }}: {{ $einvoice->buyer_id_value }}</div>
                        @if($einvoice->buyer_sst_no)
                            <div class="text-muted small">SST No: {{ $einvoice->buyer_sst_no }}</div>
                        @endif
                        <div class="text-muted small mt-2 pt-2 border-top">
                            Contact: {{ $einvoice->buyer_phone ?: 'N/A' }}<br>
                            Email: {{ $einvoice->buyer_email ?: 'N/A' }}<br>
                            Address: {{ $einvoice->buyer_address ?: 'Malaysia' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul text-primary"></i>
                        <span>Invoice Line Items</span>
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Item Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Tax Category</th>
                                <th class="text-end pe-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($einvoice->order)
                                @foreach($einvoice->order->items as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ $item->product->name ?? 'Item' }}</div>
                                            @if(!empty($item->variant_name))
                                                <small class="text-primary d-block">Variant: {{ $item->variant_name }}</small>
                                            @endif
                                            <small class="text-muted">SKU: {{ $item->product->sku ?? '-' }} &bull; Class: 022</small>
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">RM {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end"><span class="badge bg-light text-secondary border">06 - 0% Exempt</span></td>
                                        <td class="text-end pe-3 fw-semibold">RM {{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            @elseif(!empty($einvoice->ubl_payload['InvoiceLines']))
                                @foreach($einvoice->ubl_payload['InvoiceLines'] as $line)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ $line['product_name'] ?? 'Product' }}</div>
                                            <small class="text-muted">SKU: {{ $line['product_sku'] ?? '-' }} &bull; Class: 022</small>
                                        </td>
                                        <td class="text-center">{{ $line['quantity'] ?? 1 }}</td>
                                        <td class="text-end">RM {{ number_format($line['unit_price'] ?? 0, 2) }}</td>
                                        <td class="text-end"><span class="badge bg-light text-secondary border">06 - 0% Exempt</span></td>
                                        <td class="text-end pe-3 fw-semibold">RM {{ number_format($line['subtotal'] ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light p-3 border-0">
                    <div class="d-flex justify-content-between mb-1 small text-muted">
                        <span>Subtotal (Excluding Tax):</span>
                        <span class="fw-semibold text-dark">RM {{ number_format($einvoice->subtotal_amount, 2) }}</span>
                    </div>
                    @if($einvoice->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-1 small text-danger">
                            <span>Discount / Allowance:</span>
                            <span class="fw-semibold">- RM {{ number_format($einvoice->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Tax Amount (0% Exempt - Code 06):</span>
                        <span class="fw-semibold text-dark">RM {{ number_format($einvoice->tax_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-bold fs-6 text-dark">TOTAL PAYABLE AMOUNT:</span>
                        <span class="fw-bold fs-5 text-success">RM {{ number_format($einvoice->total_payable_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: LHDN Digital Stamp & UBL 2.1 JSON Payload -->
        <div class="col-lg-5">
            <!-- QR Code Card -->
            <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4 text-center">
                <h6 class="fw-bold text-dark mb-1">🇲🇾 LHDN Digital Validation QR</h6>
                <p class="text-muted small mb-3">Scan with mobile camera to verify official validation record</p>

                <div class="d-inline-block p-3 bg-light rounded-3 border mb-3">
                    <img src="{{ $einvoice->getQrCodeDataUri(140) }}" alt="QR Code" style="width: 140px; height: 140px; display: block; margin: 0 auto;">
                </div>

                <div class="text-start bg-light p-3 rounded-3 border">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Document Status:</span>
                        <span class="fw-bold text-success">{{ strtoupper($einvoice->status) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Validation Time:</span>
                        <span class="fw-semibold text-dark">{{ ($einvoice->validated_at ?? $einvoice->issued_at)->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Document Hash:</span>
                        <code class="text-muted" style="font-size: 0.72rem;">{{ Str::limit($einvoice->document_hash, 16) }}</code>
                    </div>
                </div>
            </div>

            <!-- UBL 2.1 JSON Inspector Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-code-square text-primary"></i>
                        <span>UBL 2.1 JSON Payload</span>
                    </h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('json_ubl').innerText); alert('UBL JSON copied to clipboard!');">
                        <i class="bi bi-clipboard me-1"></i>Copy JSON
                    </button>
                </div>
                <div class="card-body p-0">
                    <pre id="json_ubl" class="p-3 mb-0 rounded-bottom" style="background: #0f172a; color: #38bdf8; font-family: Consolas, monospace; font-size: 0.76rem; max-height: 420px; overflow-y: auto;">{{ json_encode($einvoice->ubl_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
