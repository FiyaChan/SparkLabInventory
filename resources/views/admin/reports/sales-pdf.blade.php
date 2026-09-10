<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report — {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; padding: 15px; }
        .header { border-bottom: 2px solid #6366f1; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 15px; margin-bottom: 18px; }
        .summary-grid { width: 100%; }
        .summary-item { font-size: 12px; color: #334155; }
        .summary-val { font-weight: bold; font-size: 14px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f1f5f9; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #475569; }
        .text-right { text-align: right; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; background: #ecfdf5; color: #065f46; }
        .footer { margin-top: 25px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ config('app.name') }} — Sales Performance Report</div>
        <div class="subtitle">Reporting Period: <strong>{{ $from->format('d M Y') }}</strong> to <strong>{{ $to->format('d M Y') }}</strong></div>
    </div>

    <table class="summary-box summary-grid">
        <tr>
            <td class="summary-item" style="border:none;">
                Total Orders Fulfilled:<br>
                <span class="summary-val">{{ number_format($summary['total_orders']) }}</span>
            </td>
            <td class="summary-item text-right" style="border:none;">
                Total Revenue Recorded:<br>
                <span class="summary-val" style="color: #4f46e5;">RM {{ number_format($summary['total_revenue'], 2) }}</span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Order Ref</th>
                <th>Customer</th>
                <th>Transaction Date</th>
                <th class="text-right">Total (RM)</th>
                <th class="text-right">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td><strong>#{{ $order->order_number }}</strong></td>
                    <td>{{ $order->user->name ?? ($order->shipping_name ?? 'N/A') }}</td>
                    <td>{{ $order->created_at->format('d M Y, H:i') }}</td>
                    <td class="text-right">RM {{ number_format($order->total_amount, 2) }}</td>
                    <td class="text-right"><span class="badge">{{ ucfirst($order->status) }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">No completed sales records found within this date range.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidential Sales Report &bull; Generated on {{ now()->format('d M Y, H:i:s') }} by {{ Auth::user()?->name ?? 'Administrator' }} &bull; {{ config('app.name') }}
    </div>
</body>
</html>
