<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\ReportDateRangeRequest;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        $this->authorize('report.view');

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $metrics = [
            'sales_today' => Order::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('total_amount'),
            'sales_this_month' => Order::where('status', 'completed')
                ->whereBetween('created_at', [$startOfMonth, $now])
                ->sum('total_amount'),
            'total_customers' => User::role('customer')->count(),
            'total_products' => Product::where('is_active', true)->count(),
            'low_stock_count' => Product::whereHas('inventory', fn ($q) => $q->whereColumn('quantity_on_hand', '<=', 'reorder_level'))->count(),
        ];

        return view('admin.reports.index', compact('metrics'));
    }

    // -------------------------------------------------------------
    // SALES REPORT
    // -------------------------------------------------------------
    public function sales(ReportDateRangeRequest $request)
    {
        $this->authorize('report.view');

        [$from, $to] = $this->resolveDateRange($request);

        $orders = Order::with('user')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $summary = [
            'total_revenue' => $orders->sum('total_amount'),
            'total_orders' => $orders->count(),
            'average_order_value' => $orders->count() > 0 ? $orders->avg('total_amount') : 0,
        ];

        return view('admin.reports.sales', compact('orders', 'summary', 'from', 'to'));
    }

    public function salesExportExcel(ReportDateRangeRequest $request): StreamedResponse
    {
        $this->authorize('report.view');
        [$from, $to] = $this->resolveDateRange($request);

        $this->logExport('sales', 'excel');

        $orders = Order::with('user')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $filename = 'sales-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Microsoft Excel automatically recognizes encoding and delimiters
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Order Number', 'Customer', 'Date', 'Items', 'Total (RM)', 'Payment Method', 'Status']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->user->name ?? 'N/A',
                    $order->created_at->format('Y-m-d H:i'),
                    $order->items->count(),
                    number_format($order->total_amount, 2, '.', ''),
                    $order->payment->method ?? '-',
                    ucfirst($order->status),
                ]);
            }
            fclose($handle);
        }, $filename, $headers);
    }

    public function salesExportPdf(ReportDateRangeRequest $request)
    {
        $this->authorize('report.view');
        [$from, $to] = $this->resolveDateRange($request);

        $orders = Order::with('user')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $summary = [
            'total_revenue' => $orders->sum('total_amount'),
            'total_orders' => $orders->count(),
        ];

        $this->logExport('sales', 'pdf');

        $pdf = Pdf::loadView('admin.reports.sales-pdf', compact('orders', 'summary', 'from', 'to'));
        return $pdf->download('sales-report-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.pdf');
    }

    // -------------------------------------------------------------
    // INVENTORY REPORT
    // -------------------------------------------------------------
    public function inventory()
    {
        $this->authorize('report.view');

        $products = Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->get();

        $summary = [
            'total_sku' => $products->count(),
            'total_stock_value' => $products->sum(fn ($p) => ($p->inventory->quantity_on_hand ?? 0) * $p->price),
            'low_stock_count' => $products->filter(fn ($p) => $p->isLowStock())->count(),
        ];

        return view('admin.reports.inventory', compact('products', 'summary'));
    }

    public function inventoryExportExcel(): StreamedResponse
    {
        $this->authorize('report.view');
        $this->logExport('inventory', 'excel');

        $products = Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->get();

        $filename = 'inventory-report-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['SKU', 'Product Name', 'Category', 'Stock On Hand', 'Reorder Level', 'Stock Status', 'Unit Price (RM)', 'Total Valuation (RM)']);

            foreach ($products as $product) {
                $qty = $product->inventory->quantity_on_hand ?? 0;
                $reorder = $product->inventory->reorder_level ?? 0;

                fputcsv($handle, [
                    $product->sku,
                    $product->name,
                    $product->category->name ?? '-',
                    $qty,
                    $reorder,
                    $qty <= $reorder ? 'LOW STOCK' : 'IN STOCK',
                    number_format($product->price, 2, '.', ''),
                    number_format($qty * $product->price, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, $filename, $headers);
    }

    public function inventoryExportPdf()
    {
        $this->authorize('report.view');
        $products = Product::with(['category', 'inventory'])->where('is_active', true)->get();

        $this->logExport('inventory', 'pdf');

        $pdf = Pdf::loadView('admin.reports.inventory-pdf', compact('products'));
        return $pdf->download('inventory-report-' . now()->format('Ymd') . '.pdf');
    }

    // -------------------------------------------------------------
    // CUSTOMER REPORT
    // -------------------------------------------------------------
    public function customers()
    {
        $this->authorize('report.view');

        $customers = User::role('customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', 'completed')], 'total_amount')
            ->latest()
            ->paginate(20);

        return view('admin.reports.customers', compact('customers'));
    }

    public function customersExportExcel(): StreamedResponse
    {
        $this->authorize('report.view');
        $this->logExport('customers', 'excel');

        $customers = User::role('customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', 'completed')], 'total_amount')
            ->get();

        $filename = 'customer-report-' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Customer Name', 'Email Address', 'Registration Date', 'Total Orders', 'Total Spent (RM)', 'Account Status']);

            foreach ($customers as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d'),
                    $user->orders_count,
                    number_format($user->total_spent ?? 0, 2, '.', ''),
                    $user->is_active ? 'Active' : 'Disabled',
                ]);
            }
            fclose($handle);
        }, $filename, $headers);
    }

    // -------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------
    protected function resolveDateRange(ReportDateRangeRequest $request): array
    {
        $validated = $request->validated();

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    protected function logExport(string $reportType, string $format): void
    {
        ActivityLog::record('report.exported', [
            'report_type' => $reportType,
            'format' => $format,
        ]);
    }
}
