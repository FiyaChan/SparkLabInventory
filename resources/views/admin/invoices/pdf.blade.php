<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->order_number }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.5; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 25px; border-bottom: 2px solid #0284c7; padding-bottom: 15px; }
        .company-name { font-size: 22px; font-weight: bold; color: #0f172a; }
        .invoice-title { font-size: 14px; font-weight: 600; color: #0284c7; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background-color: #f8fafc; font-weight: 600; font-size: 11px; text-transform: uppercase; color: #64748b; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #0f172a; font-size: 13px; color: #0f172a; }
        .status-badge { padding: 3px 8px; border-radius: 4px; background: #ecfdf5; color: #065f46; font-weight: 600; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-name">{{ config('app.name') }}</div>
            <div class="invoice-title">Official Laboratory Consignment Invoice</div>
            <div style="margin-top: 6px;">Invoice Ref: <strong>#{{ $order->order_number }}</strong></div>
            <div>Date Issued: {{ $order->created_at->format('d M Y, H:i') }}</div>
        </div>
        <div style="text-align: right;">
            <strong>Consigned To (Researcher):</strong><br>
            {{ $order->shipping_name }}<br>
            Phone: {{ $order->shipping_phone }}<br>
            {{ $order->shipping_address }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Specimen / Product</th>
                <th>SKU Code</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td><strong>{{ $item->product->name ?? 'N/A' }}</strong></td>
                    <td><code>{{ $item->product->sku ?? '-' }}</code></td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">RM {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">RM {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="text-right">Total Requisition Amount:</td>
                <td class="text-right">RM {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
        <strong>Payment Information:</strong><br>
        Payment Mode: {{ $order->payment->method === 'cod' ? 'Cash on Delivery (COD)' : 'Online Banking Simulation' }}<br>
        Payment Status: <span class="status-badge">{{ ucfirst($order->payment->status ?? 'pending') }}</span><br>
        Order Fulfillment Status: <strong>{{ ucfirst($order->status) }}</strong>
    </div>

    <p style="margin-top: 35px; font-size: 10px; color: #94a3b8; text-align: center;">
        Computer-generated commercial invoice by {{ config('app.name') }}. Compliant with laboratory inventory records.
    </p>
</body>
</html>
