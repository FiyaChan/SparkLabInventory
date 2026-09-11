<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LHDN e-Invoice Verification — {{ $eInvoice->irbm_unique_id }}</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            padding: 24px 12px;
        }
        .verify-card {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .verify-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 24px 28px;
            color: #ffffff;
            position: relative;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #10b981;
            color: #ffffff;
            padding: 6px 14px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.03em;
        }
        .meta-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
        }
        .code-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
            color: #2563eb;
            word-break: break-all;
        }
        .section-divider {
            border-top: 1px solid #f1f5f9;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <!-- Header Banner -->
        <div class="verify-header">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-patch-check-fill text-info fs-4"></i>
                        <span class="fs-5 fw-bold">LHDN MyInvois Validation Portal</span>
                    </div>
                    <div class="text-white-50 small">Official Inland Revenue Board of Malaysia Compliance Record</div>
                </div>
                <div class="status-pill">
                    <i class="bi bi-shield-fill-check"></i>
                    {{ strtoupper($eInvoice->status) }}
                </div>
            </div>
        </div>

        <div class="p-4">
            <!-- Alert Badge -->
            <div class="alert alert-success d-flex align-items-center gap-3 border-0 bg-success-subtle text-success-emphasis rounded-3 p-3 mb-4">
                <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                <div>
                    <div class="fw-bold">Valid & Verified Document</div>
                    <div class="small">This e-Invoice document has been digitally verified and matches the official IRBM compliance ledger.</div>
                </div>
            </div>

            <!-- IRBM UUID & Document Hash -->
            <div class="mb-4">
                <div class="meta-label">LHDN Unique Identifier (UUID)</div>
                <div class="code-box fw-bold d-flex align-items-center justify-content-between">
                    <span>{{ $eInvoice->irbm_unique_id }}</span>
                    <i class="bi bi-qr-code text-muted"></i>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="meta-label">Invoice Number</div>
                    <div class="meta-value text-primary">{{ $eInvoice->invoice_number }}</div>
                </div>
                <div class="col-6">
                    <div class="meta-label">Date & Time Validated</div>
                    <div class="meta-value">{{ ($eInvoice->validated_at ?? $eInvoice->created_at)->format('d M Y, h:i A') }}</div>
                </div>
                <div class="col-6">
                    <div class="meta-label">Document Type</div>
                    <div class="meta-value">
                        <span class="badge bg-secondary-subtle text-secondary border">
                            {{ $eInvoice->invoice_type === 'consolidated' ? 'Consolidated e-Invoice' : '01 - Standard Invoice' }}
                        </span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="meta-label">Origin Channel</div>
                    <div class="meta-value text-uppercase">
                        <span class="badge {{ $eInvoice->source === 'pos' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary' }}">
                            {{ $eInvoice->source === 'pos' ? 'POS Counter Terminal' : 'E-Commerce Online' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Tax Entities Breakdown -->
            <div class="row g-4 mb-4">
                <!-- Supplier (Seller) -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold small">
                            <i class="bi bi-building"></i>
                            <span>SUPPLIER (PENJUAL)</span>
                        </div>
                        <div class="fw-bold fs-6">{{ $eInvoice->supplier_name }}</div>
                        <div class="small text-muted mb-2">TIN: <strong>{{ $eInvoice->supplier_tin }}</strong></div>
                        <div class="small text-muted">SSM/BRN: {{ $eInvoice->supplier_id_value }}</div>
                        <div class="small text-muted">MSIC: {{ $eInvoice->supplier_msic_code }} ({{ $eInvoice->supplier_msic_desc ?? 'Retail Goods' }})</div>
                        @if($eInvoice->supplier_sst_no)
                            <div class="small text-muted">SST No: {{ $eInvoice->supplier_sst_no }}</div>
                        @endif
                    </div>
                </div>

                <!-- Buyer (Customer) -->
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="d-flex align-items-center gap-2 mb-2 text-primary fw-bold small">
                            <i class="bi bi-person-badge"></i>
                            <span>BUYER (PEMBELI)</span>
                        </div>
                        <div class="fw-bold fs-6">{{ $eInvoice->buyer_name }}</div>
                        <div class="small text-muted mb-2">TIN: <strong>{{ $eInvoice->buyer_tin }}</strong></div>
                        <div class="small text-muted">{{ $eInvoice->buyer_id_type }}: {{ $eInvoice->buyer_id_value }}</div>
                        <div class="small text-muted">Contact: {{ $eInvoice->buyer_phone ?: 'N/A' }}</div>
                        <div class="small text-muted text-truncate">Address: {{ $eInvoice->buyer_address ?: 'Malaysia' }}</div>
                    </div>
                </div>
            </div>

            <!-- Financials Summary -->
            <div class="card border rounded-3 p-3 mb-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Total Excluding Tax:</span>
                    <span class="fw-semibold">RM {{ number_format($eInvoice->total_payable_amount - $eInvoice->tax_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Tax Amount ({{ $eInvoice->tax_type_code === '06' ? '0% Exempt' : $eInvoice->tax_rate.'%' }}):</span>
                    <span class="fw-semibold">RM {{ number_format($eInvoice->tax_amount, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <span class="fw-bold fs-6">TOTAL PAYABLE AMOUNT:</span>
                    <span class="fw-bold fs-5 text-success">RM {{ number_format($eInvoice->total_payable_amount, 2) }}</span>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2">
                @if($eInvoice->order)
                    <a href="{{ route('orders.invoice', $eInvoice->order->id) }}" class="btn btn-primary btn-sm px-3 rounded-pill">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download Official e-Invoice PDF
                    </a>
                @endif
                <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Verification
                </button>
            </div>
        </div>

        <div class="bg-light p-3 text-center border-top text-muted small">
            <div>Digital Verification Powered by <strong>SparkLab LHDN MyInvois Engine</strong></div>
            <div class="text-muted" style="font-size: 0.72rem;">Compliant with Inland Revenue Board of Malaysia (IRBM) e-Invoicing Guidelines &bull; UBL 2.1 JSON Standard</div>
        </div>
    </div>
</body>
</html>
