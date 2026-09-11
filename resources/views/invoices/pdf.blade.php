<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>e-Invoice #{{ $order->eInvoice->invoice_number ?? $order->order_number }} — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
            padding: 15px;
            margin: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .company-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .einvoice-badge {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .uuid-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            color: #1d4ed8;
            margin-top: 4px;
            word-break: break-all;
        }
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .party-box {
            width: 48%;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 6px;
        }
        .party-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            padding: 7px 8px;
            text-align: left;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 10px;
        }
        .grand-total-row td {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            background-color: #f1f5f9;
            padding: 6px 8px;
        }
        .footer-section {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    @php
        $inv = $order->eInvoice;
    @endphp

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="company-title">{{ config('app.name', 'SparkLab Kids Science') }}</div>
                <div style="font-size: 11px; font-weight: 600; color: #2563eb; margin-top: 2px;">
                    OFFICIAL ELECTRONIC TAX INVOICE (e-INVOIS LHDN)
                </div>
                <div style="font-size: 9px; color: #64748b; margin-top: 3px;">
                    Compliant with Inland Revenue Board of Malaysia (IRBM / LHDN) UBL 2.1 Standard
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <span class="einvoice-badge">
                    {{ $inv ? strtoupper($inv->status) . ' / DISAHKAN' : 'OFFICIAL INVOICE' }}
                </span>
                <div style="font-size: 10px; margin-top: 4px;">
                    e-Invoice No: <strong>{{ $inv->invoice_number ?? ('INV-' . $order->order_number) }}</strong>
                </div>
                <div style="font-size: 9px; color: #475569;">
                    Date Issued: {{ ($inv->issued_at ?? $order->created_at)->format('d M Y, H:i:s') }}
                </div>
                <div style="font-size: 9px; color: #475569;">
                    Internal Ref: #{{ $order->order_number }}
                </div>
            </td>
        </tr>
    </table>

    @if($inv)
        <div style="margin-bottom: 12px;">
            <span style="font-size: 9px; font-weight: bold; color: #475569;">LHDN Unique Identifier (UUID):</span>
            <div class="uuid-box">{{ $inv->irbm_unique_id }}</div>
        </div>
    @endif

    <!-- Parties Information (Supplier & Buyer) -->
    <table class="parties-table">
        <tr>
            <!-- Supplier -->
            <td class="party-box">
                <div class="party-title">Supplier Details (Penjual)</div>
                <div style="font-weight: bold; font-size: 11px;">{{ $inv->supplier_name ?? config('app.name') }}</div>
                <div style="margin-top: 3px;">TIN: <strong>{{ $inv->supplier_tin ?? 'C25890123040' }}</strong></div>
                <div>BRN / SSM: {{ $inv->supplier_id_value ?? '202601009988' }}</div>
                <div>MSIC: {{ $inv->supplier_msic_code ?? '47630' }} ({{ $inv->supplier_msic_desc ?? 'Retail of Educational Supplies' }})</div>
                @if(!empty($inv->supplier_sst_no))
                    <div>SST Reg No: {{ $inv->supplier_sst_no }}</div>
                @endif
                <div style="margin-top: 3px; color: #475569;">
                    {{ $inv->supplier_address ?? 'Lot 4.12, Discovery Mall, 62000 Putrajaya, Malaysia' }}<br>
                    Tel: {{ $inv->supplier_phone ?? '+60 3-8888 1234' }} | Email: {{ $inv->supplier_email ?? 'einvoice@sparklab.my' }}
                </div>
            </td>
            <td style="width: 4%;"></td>
            <!-- Buyer -->
            <td class="party-box">
                <div class="party-title">Buyer Details (Pembeli)</div>
                <div style="font-weight: bold; font-size: 11px;">{{ $inv->buyer_name ?? $order->shipping_name }}</div>
                <div style="margin-top: 3px;">TIN: <strong>{{ $inv->buyer_tin ?? ($order->buyer_tin ?: 'EI00000000020') }}</strong></div>
                <div>{{ $inv->buyer_id_type ?? 'NRIC' }}: {{ $inv->buyer_id_value ?? ($order->buyer_id_number ?: '000000000000') }}</div>
                @if(!empty($inv->buyer_sst_no) || !empty($order->buyer_sst_no))
                    <div>SST No: {{ $inv->buyer_sst_no ?? $order->buyer_sst_no }}</div>
                @endif
                <div style="margin-top: 3px; color: #475569;">
                    Contact: {{ $order->shipping_phone }}<br>
                    Email: {{ $order->user->email ?? 'N/A' }}<br>
                    Address: {{ $order->shipping_address }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Itemized List Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Description / Item</th>
                <th style="width: 15%;">Classification</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 15%;">Unit Price</th>
                <th class="text-right" style="width: 15%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $calcSub = 0; @endphp
            @foreach ($order->items as $idx => $item)
                @php $calcSub += $item->subtotal; @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? 'Science Kit' }}</strong>
                        @if(!empty($item->variant_name))
                            <div style="font-size: 8.5px; color: #2563eb;">Variant: {{ $item->variant_name }}</div>
                        @endif
                        <div style="font-size: 8.5px; color: #64748b;">SKU: {{ $item->product->sku ?? '-' }}</div>
                    </td>
                    <td>
                        <span style="font-size: 9px; color: #475569;">022 (General Goods)</span>
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">RM {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">RM {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Breakdown & QR Code Section -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- QR Code & Digital Signature Verification Box -->
            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
                @if($inv)
                    <div style="border: 1px solid #cbd5e1; background-color: #f8fafc; border-radius: 6px; padding: 10px; text-align: center;">
                        <div style="font-size: 10px; font-weight: bold; color: #0f172a;">
                            🇲🇾 LHDN MyInvois Digital QR Verification
                        </div>
                        <div style="margin: 6px 0;">
                            <img src="{{ $inv->getQrCodeDataUri(110) }}" style="width: 100px; height: 100px; display: inline-block;">
                        </div>
                        <div style="font-size: 8px; color: #475569;">
                            Scan with smartphone camera to verify this e-Invoice directly with the official IRBM compliance ledger.
                        </div>
                    </div>
                @endif
            </td>

            <!-- Financial Totals -->
            <td style="width: 50%; vertical-align: top;">
                <table class="totals-table">
                    <tr>
                        <td class="text-right" style="color: #475569;">Subtotal (Excluding Tax):</td>
                        <td class="text-right" style="font-weight: 600; width: 35%;">RM {{ number_format($calcSub, 2) }}</td>
                    </tr>
                    @if($calcSub > $order->total_amount)
                        <tr>
                            <td class="text-right" style="color: #dc2626;">Discount / Allowance:</td>
                            <td class="text-right" style="color: #dc2626; font-weight: 600;">- RM {{ number_format($calcSub - $order->total_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-right" style="color: #475569;">Tax (0% Tax Exempt / Category 06):</td>
                        <td class="text-right" style="font-weight: 600;">RM 0.00</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="text-right">TOTAL PAYABLE AMOUNT:</td>
                        <td class="text-right">RM {{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </table>

                <div style="margin-top: 10px; background-color: #f1f5f9; padding: 8px; border-radius: 4px; font-size: 9px;">
                    <div>Payment Method: <strong>{{ strtoupper($order->payment->method ?? 'N/A') }}</strong></div>
                    <div>Payment Status: <strong style="color: #16a34a;">{{ strtoupper($order->payment->status ?? 'PAID') }}</strong></div>
                    @if($order->payment && $order->payment->transaction_ref)
                        <div>Gateway Ref: <code>{{ $order->payment->transaction_ref }}</code></div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer Notice -->
    <div class="footer-section">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 8px; color: #94a3b8; line-height: 1.3;">
                    This is a computer-generated electronic invoice validated under the Inland Revenue Board of Malaysia (LHDN) e-Invoicing guidelines. No physical signature is required.
                </td>
                <td style="font-size: 8px; color: #94a3b8; text-align: right; width: 30%;">
                    Generated by {{ config('app.name') }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
