<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'salesStats' => $this->salesStats(),
            'inventoryOverview' => $this->inventoryOverview(),
            'lowStockProducts' => $this->lowStockProducts(),
            'recentOrders' => $this->recentOrders(),
            'securityAlerts' => $this->securityAlerts(),
            'salesChartData' => $this->salesChartData(),
        ]);
    }

    /**
     * Sales Statistics — today, this month, and all-time, plus order count.
     * Only counts 'completed' orders as revenue — a pending/cancelled order
     * shouldn't inflate the sales figure the admin sees.
     */
    protected function salesStats(): array
    {
        $baseQuery = fn () => Order::where('status', 'completed');

        return [
            'today' => (clone $baseQuery())->whereDate('created_at', today())->sum('total_amount'),
            'this_month' => (clone $baseQuery())->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->sum('total_amount'),
            'all_time' => (clone $baseQuery())->sum('total_amount'),
            'order_count_today' => (clone $baseQuery())->whereDate('created_at', today())->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];
    }

    /**
     * Inventory Overview — headline counts an admin scans in 2 seconds:
     * total SKUs, total units held, and how many are below reorder level.
     */
    protected function inventoryOverview(): array
    {
        return [
            'total_products' => Product::where('is_active', true)->count(),
            'total_units' => DB::table('inventory')->sum('quantity_on_hand'),
            'low_stock_count' => DB::table('inventory')
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->count(),
            'out_of_stock_count' => DB::table('inventory')->where('quantity_on_hand', 0)->count(),
        ];
    }

    protected function lowStockProducts()
    {
        return Product::with('inventory')
            ->whereHas('inventory', fn ($q) => $q->whereColumn('quantity_on_hand', '<=', 'reorder_level'))
            ->limit(8)
            ->get();
    }

    protected function recentOrders()
    {
        return Order::with('user:id,name')->latest()->limit(8)->get();
    }

    /**
     * Security Alerts — surfaces the specific activity_log actions that
     * actually matter operationally: failed logins, denied access attempts,
     * and MFA failures. This is what turns the passive audit trail from
     * Sprint 1/2 into an actionable dashboard widget, per the FYP spec.
     */
    protected function securityAlerts()
    {
        return ActivityLog::with('user:id,name,email')
            ->whereIn('action', ['login.failed', 'access.denied', 'mfa.failed'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Last 7 days of completed-order revenue, for the dashboard chart.
     * Returns dense data (every day present, even with 0 sales) so the
     * chart doesn't show misleading gaps.
     */
    protected function salesChartData(): array
    {
        $raw = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('D');
            $values[] = (float) ($raw[$date] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
