<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Valuation Report — {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; padding: 15px; }
        .header { border-bottom: 2px solid #0284c7; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f1f5f9; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #475569; }
        .text-right { text-align: right; }
        .low { color: #dc2626; font-weight: bold; background: #fef2f2; }
        .badge-ok { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; background: #ecfdf5; color: #065f46; }
        .badge-low { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; background: #fef2f2; color: #dc2626; }
        .footer { margin-top: 25px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ config('app.name') }} — Inventory Valuation & Stock Ledger</div>
        <div class="subtitle">Snapshot generated on: <strong>{{ now()->format('d M Y, H:i') }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU Code</th>
                <th>Product / Kit Name</th>
                <th>Category</th>
                <th class="text-right">Stock On Hand</th>
                <th class="text-right">Reorder Level</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Stock Valuation</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php
                    $qty = $product->inventory->quantity_on_hand ?? 0;
                    $reorder = $product->inventory->reorder_level ?? 0;
                    $isLow = $qty <= $reorder;
                @endphp
                <tr class="{{ $isLow ? 'low' : '' }}">
                    <td><code>{{ $product->sku }}</code></td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>{{ $product->category->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($qty) }}</td>
                    <td class="text-right">{{ number_format($reorder) }}</td>
                    <td class="text-right">RM {{ number_format($product->price, 2) }}</td>
                    <td class="text-right">RM {{ number_format($qty * $product->price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 20px;">No product inventory records available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidential Inventory Ledger &bull; Generated on {{ now()->format('d M Y, H:i:s') }} by {{ Auth::user()?->name ?? 'Administrator' }} &bull; {{ config('app.name') }}
    </div>
</body>
</html>
