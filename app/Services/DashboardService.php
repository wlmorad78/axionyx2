<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Get configured widgets for a user's role.
     */
    public function getWidgets(User $user): array
    {
        $roleId = $user->role_id;
        if (!$roleId) {
            return $this->getDefaultWidgets();
        }

        $cacheKey = "dashboard_widgets:role:{$roleId}";

        return Cache::remember($cacheKey, 3600, function () use ($roleId) {
            $widgets = DashboardWidget::where('is_active', true)
                ->whereHas('roles', function ($q) use ($roleId) {
                    $q->where('role_id', $roleId)
                      ->where('role_widgets.is_visible', true);
                })
                ->withPivot(['sort_order', 'width', 'config'])
                ->orderBy('role_widgets.sort_order')
                ->get();

            if ($widgets->isEmpty()) {
                return $this->getDefaultWidgets();
            }

            return $widgets->map(function ($w) {
                return [
                    'code' => $w->code,
                    'name' => $w->name,
                    'name_ar' => $w->name_ar,
                    'category' => $w->category,
                    'widget_type' => $w->widget_type,
                    'sort_order' => $w->pivot->sort_order ?? $w->default_sort_order,
                    'width' => $w->pivot->width ?? $w->default_width,
                    'config' => $w->pivot->config,
                ];
            })->toArray();
        });
    }

    /**
     * Get widget data based on widget code.
     */
    public function getWidgetData(User $user, string $code): ?array
    {
        $company = $user->company;
        if (!$company) return null;

        return match($code) {
            'counters' => $this->getCounters($company),
            'today_activity' => $this->getTodayActivity($company),
            'month_summary' => $this->getMonthSummary($company),
            'finance_summary' => $this->getFinanceSummary($company),
            'sales_chart' => $this->getSalesChart($company),
            'recent_sales' => $this->getRecentSales($company),
            'unpaid_invoices' => $this->getUnpaidInvoices($company),
            'top_customers' => $this->getTopCustomers($company),
            'low_stock' => $this->getLowStock($company),
            'purchase_today' => $this->getPurchaseToday($company),
            'employee_count' => $this->getEmployeeCount($company),
            'attendance_today' => $this->getAttendanceToday($company),
            default => null,
        };
    }

    /**
     * Build full dashboard response.
     */
    public function buildDashboard(User $user): array
    {
        $widgets = $this->getWidgets($user);
        $data = [];
        foreach ($widgets as $widget) {
            $widgetData = $this->getWidgetData($user, $widget['code']);
            $data[] = array_merge($widget, ['data' => $widgetData]);
        }
        return $data;
    }

    protected function getDefaultWidgets(): array
    {
        return [
            ['code' => 'counters', 'name' => 'Counters', 'name_ar' => 'العدّادات', 'category' => 'general', 'widget_type' => 'counters', 'sort_order' => 0, 'width' => 3, 'config' => null],
            ['code' => 'today_activity', 'name' => 'Today Activity', 'name_ar' => 'نشاط اليوم', 'category' => 'general', 'widget_type' => 'activity', 'sort_order' => 1, 'width' => 2, 'config' => null],
            ['code' => 'month_summary', 'name' => 'Month Summary', 'name_ar' => 'ملخص الشهر', 'category' => 'general', 'widget_type' => 'summary', 'sort_order' => 2, 'width' => 2, 'config' => null],
            ['code' => 'finance_summary', 'name' => 'Finance Summary', 'name_ar' => 'الملخص المالي', 'category' => 'finance', 'widget_type' => 'finance', 'sort_order' => 3, 'width' => 2, 'config' => null],
            ['code' => 'recent_sales', 'name' => 'Recent Sales', 'name_ar' => 'آخر فواتير المبيعات', 'category' => 'sales', 'widget_type' => 'list', 'sort_order' => 4, 'width' => 2, 'config' => null],
        ];
    }

    protected function getCounters($company): array
    {
        return [
            'customers' => $company->customers()->count(),
            'suppliers' => $company->suppliers()->count(),
            'items' => \App\Models\Item::where('company_id', $company->id)->count(),
            'employees' => $company->employees()->count(),
        ];
    }

    protected function getTodayActivity($company): array
    {
        $today = now()->toDateString();
        return [
            'sales_count' => $company->invoices()->whereDate('invoice_date', $today)->count(),
            'sales_total' => $company->invoices()->whereDate('invoice_date', $today)->sum('net_total'),
            'purchases_count' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereDate('invoice_date', $today)->count(),
            'purchases_total' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereDate('invoice_date', $today)->sum('net_total'),
        ];
    }

    protected function getMonthSummary($company): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        return [
            'sales_count' => $company->invoices()->whereBetween('invoice_date', [$start, $end])->count(),
            'sales_total' => $company->invoices()->whereBetween('invoice_date', [$start, $end])->sum('net_total'),
            'purchases_count' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereBetween('invoice_date', [$start, $end])->count(),
            'purchases_total' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereBetween('invoice_date', [$start, $end])->sum('net_total'),
        ];
    }

    protected function getFinanceSummary($company): array
    {
        $treasury = $company->treasuries()->sum('opening_balance');
        $totalSales = $company->invoices()->where('status', 'posted')->sum('net_total');
        $totalCollected = $company->customerPayments()->sum('amount');
        return [
            'treasury_balance' => $treasury,
            'unpaid_sales' => max(0, $totalSales - $totalCollected),
        ];
    }

    protected function getSalesChart($company): array
    {
        return $company->invoices()
            ->selectRaw('invoice_date, SUM(net_total) as total')
            ->where('invoice_date', '>=', now()->subDays(7))
            ->groupBy('invoice_date')
            ->orderBy('invoice_date')
            ->get()
            ->toArray();
    }

    protected function getRecentSales($company): array
    {
        return $company->invoices()
            ->with('customer:id,name_ar,name_en')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($inv) => [
                'invoice_no' => $inv->invoice_no,
                'invoice_date' => $inv->invoice_date,
                'net_total' => $inv->net_total,
                'status' => $inv->status,
                'customer_name' => $inv->customer->name_ar ?? $inv->customer->name_en ?? '',
            ])
            ->toArray();
    }

    protected function getUnpaidInvoices($company): array
    {
        $invoices = $company->invoices()->where('status', 'posted')->get();
        $totalUnpaid = 0;
        $count = 0;
        foreach ($invoices as $inv) {
            $paid = $company->customerPayments()
                ->where('customer_id', $inv->customer_id)
                ->sum('amount');
            if ($paid < $inv->net_total) {
                $totalUnpaid += ($inv->net_total - $paid);
                $count++;
            }
        }
        return ['count' => $count, 'total' => $totalUnpaid];
    }

    protected function getTopCustomers($company): array
    {
        return $company->invoices()
            ->selectRaw('customer_id, SUM(net_total) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->take(5)
            ->with('customer:id,name_ar,name_en')
            ->get()
            ->map(fn($row) => [
                'customer_name' => $row->customer->name_ar ?? $row->customer->name_en ?? 'N/A',
                'total' => $row->total,
            ])
            ->toArray();
    }

    protected function getLowStock($company): array
    {
        return \App\Models\Item::where('company_id', $company->id)
            ->whereColumn('reorder_quantity', '>', 'minimum_stock')
            ->take(10)
            ->get(['id', 'name_ar', 'name_en', 'reorder_quantity', 'minimum_stock'])
            ->toArray();
    }

    protected function getPurchaseToday($company): array
    {
        $today = now()->toDateString();
        return [
            'count' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereDate('invoice_date', $today)->count(),
            'total' => \App\Models\PurchaseInvoice::where('company_id', $company->id)->whereDate('invoice_date', $today)->sum('net_total'),
        ];
    }

    protected function getEmployeeCount($company): array
    {
        return [
            'total' => $company->employees()->count(),
            'active' => $company->employees()->where('status', 'active')->count(),
        ];
    }

    protected function getAttendanceToday($company): array
    {
        $today = now()->toDateString();
        return [
            'total' => $company->attendances()->whereDate('attendance_date', $today)->count(),
            'checked_in' => $company->attendances()->whereDate('attendance_date', $today)->whereNotNull('check_in')->count(),
        ];
    }
}
