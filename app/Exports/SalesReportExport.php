<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Carbon;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected Carbon $from, protected Carbon $to) {}

    public function collection()
    {
        // The date range comes from validated Carbon instances constructed
        // in the controller (see SalesReportRequest below) — never raw
        // strings passed straight into whereBetween(), even though Eloquent
        // parameter binding would neutralize SQL injection either way;
        // validating early also catches nonsensical ranges before we query.
        return Order::with('user')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return ['Order Number', 'Customer', 'Date', 'Items', 'Total (RM)', 'Payment Method', 'Status'];
    }

    public function map($order): array
    {
        return [
            $order->order_number,
            $order->user->name ?? 'N/A',
            $order->created_at->format('Y-m-d H:i'),
            $order->items->count(),
            number_format($order->total_amount, 2),
            $order->payment->method ?? '-',
            ucfirst($order->status),
        ];
    }
}
