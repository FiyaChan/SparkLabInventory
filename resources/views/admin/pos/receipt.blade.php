<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->order_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 12px;
            line-height: 1.35;
            color: #000000;
            background: #ffffff;
            padding: 12px 10px;
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        .header {
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header .store-name {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.05em;
        }
        .header .store-tagline {
            font-size: 10px;
            margin-top: 2px;
        }

        .meta-info {
            font-size: 11px;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.items-table th {
            border-bottom: 1px solid #000;
            font-size: 11px;
            padding: 3px 0;
        }
        table.items-table td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 11px;
        }

        .totals-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 6px 0;
            margin-bottom: 8px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 2px;
        }
        .grand-total-row {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #000;
        }

        .footer {
            font-size: 10px;
            padding-top: 6px;
            line-height: 1.4;
        }

        .barcode-box {
            margin: 8px 0;
            letter-spacing: 4px;
            font-size: 14px;
            font-weight: bold;
        }

        .no-print {
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-print {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            margin: 4px;
        }
        .btn-close-window {
            background: #e2e8f0;
            color: #334155;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin: 4px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    @if(!isset($isPdf) || !$isPdf)
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
            <button class="btn-close-window" onclick="window.close()">✕ Close</button>
        </div>
    @endif

    <div class="receipt-container">
        <!-- Store Header -->
        <div class="header text-center">
            <div class="store-name">{{ config('app.name', 'SparkLab Kids Science') }}</div>
            <div class="store-tagline">Retail & Educational Inventory</div>
            <div style="font-size: 10px; color: #444;">Lot 4.12, Discovery Mall, Putrajaya</div>
            <div style="font-size: 9px; color: #444;">Tel: +60 3-8888 1234 &bull; SSM/BRN: 202601009988</div>
            @if($order->eInvoice)
                <div style="font-size: 9px; font-weight: bold; margin-top: 2px;">Supplier TIN: {{ $order->eInvoice->supplier_tin }} | MSIC: {{ $order->eInvoice->supplier_msic_code }}</div>
            @endif
        </div>

        <!-- Meta Details -->
        <div class="meta-info">
            <div class="meta-row">
                <span>Receipt #:</span>
                <span class="fw-bold">{{ $order->order_number }}</span>
            </div>
            @if($order->eInvoice)
                <div class="meta-row">
                    <span>LHDN e-Inv #:</span>
                    <span class="fw-bold" style="font-size: 10px;">{{ $order->eInvoice->invoice_number }}</span>
                </div>
            @endif
            <div class="meta-row">
                <span>Date/Time:</span>
                <span>{{ $order->created_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="meta-row">
                <span>Cashier:</span>
                <span>{{ Auth::user()->name ?? 'Cashier #1' }}</span>
            </div>
            <div class="meta-row">
                <span>Customer:</span>
                <span>{{ $order->shipping_name ?? 'Walk-in Customer' }}</span>
            </div>
            @if($order->eInvoice)
                <div class="meta-row">
                    <span>Buyer TIN:</span>
                    <span>{{ $order->eInvoice->buyer_tin }}</span>
                </div>
            @endif
        </div>

        <!-- Itemized List -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-start" style="width: 50%;">Item</th>
                    <th class="text-center" style="width: 15%;">Qty</th>
                    <th class="text-end" style="width: 17%;">Price</th>
                    <th class="text-end" style="width: 18%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $calcSubtotal = 0; @endphp
                @foreach($order->items as $item)
                    @php 
                        $calcSubtotal += $item->subtotal;
                    @endphp
                    <tr>
                        <td class="text-start">
                            <div class="fw-bold">{{ $item->product->name ?? 'Product' }}</div>
                            @if(!empty($item->variant_name))
                                <div style="font-size: 9px; color: #2563eb; font-weight: 600;">Option: {{ $item->variant_name }}</div>
                            @endif
                            <div style="font-size: 9px; color: #666;">SKU: {{ ($item->variation && $item->variation->sku) ? $item->variation->sku : ($item->product->sku ?? 'N/A') }}</div>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end fw-bold">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Payment Breakdown -->
        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>RM {{ number_format($calcSubtotal, 2) }}</span>
            </div>
            @if($calcSubtotal > $order->total_amount)
                <div class="total-row">
                    <span>Discount:</span>
                    <span>- RM {{ number_format($calcSubtotal - $order->total_amount, 2) }}</span>
                </div>
            @endif
            <div class="total-row">
                <span>Tax (0% Tax Exempt):</span>
                <span>RM 0.00</span>
            </div>
            <div class="total-row grand-total-row">
                <span>TOTAL DUE:</span>
                <span>RM {{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="meta-info">
            <div class="meta-row">
                <span>Payment Method:</span>
                <span class="fw-bold text-uppercase">{{ $order->payment->method ?? 'CASH' }}</span>
            </div>
            @if($order->payment && $order->payment->method === 'cash')
                <div class="meta-row">
                    <span>Tendered:</span>
                    <span>RM {{ number_format($order->payment->amount, 2) }}</span>
                </div>
                <div class="meta-row">
                    <span>Change:</span>
                    <span>RM 0.00</span>
                </div>
            @endif
            <div class="meta-row">
                <span>Status:</span>
                <span class="fw-bold text-uppercase">PAID</span>
            </div>
            @if($order->payment && $order->payment->transaction_ref)
                <div class="meta-row">
                    <span>Ref:</span>
                    <span style="font-size: 9px;">{{ $order->payment->transaction_ref }}</span>
                </div>
            @endif
        </div>

        <!-- LHDN MyInvois QR Code & Digital Verification Section -->
        @if($order->eInvoice)
            <div style="border: 1px dashed #000; border-radius: 4px; padding: 6px; margin-bottom: 8px; text-align: center;">
                <div style="font-size: 10px; font-weight: bold; letter-spacing: 0.04em;">🇲🇾 LHDN MyInvois Digital Validation</div>
                <div style="font-size: 8px; color: #555; margin-top: 1px;">UUID: {{ $order->eInvoice->irbm_unique_id }}</div>
                
                <div style="margin: 6px auto; display: inline-block; background: #fff; padding: 4px;">
                    <img src="{{ $order->eInvoice->getQrCodeDataUri(110) }}" alt="LHDN QR Code" style="width: 105px; height: 105px; display: block; margin: 0 auto;">
                </div>

                <div style="font-size: 8px; color: #444;">Scan with smartphone camera to verify official e-Invoice validity</div>
            </div>
        @endif

        <!-- Barcode / Footer -->
        <div class="footer text-center">
            <div class="barcode-box">*{{ $order->order_number }}*</div>
            <p>Thank you for shopping with us!</p>
            <p>Please keep this receipt as proof of purchase.</p>
            <p style="margin-top: 4px; font-size: 9px;">Goods sold are returnable within 7 days with original receipt.</p>
        </div>
    </div>

    @if(!isset($isPdf) || !$isPdf)
        <script>
            window.addEventListener('load', function() {
                // Auto trigger print prompt on load
                setTimeout(function() {
                    window.print();
                }, 300);
            });
        </script>
    @endif
</body>
</html>
