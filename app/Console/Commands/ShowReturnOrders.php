<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowReturnOrders extends Command
{
    protected $signature = 'return-orders:show
        {--status= : Filter by status (pending, approved, rejected)}
        {--from= : Start date (YYYY-MM-DD)}
        {--to= : End date (YYYY-MM-DD)}
        {--employee= : Employee ID}
        {--limit=20 : Number of orders to show}
        {--items : Show items for each order}
        {--order= : Show specific order by ID}';
    protected $description = 'Show salesman return orders with optional items';

    public function handle()
    {
        $status = $this->option('status');
        $from = $this->option('from');
        $to = $this->option('to');
        $employee = $this->option('employee');
        $limit = (int) $this->option('limit');
        $showItems = $this->option('items');
        $orderId = $this->option('order');

        if ($orderId) {
            return $this->showSingleOrder($orderId);
        }

        $query = DB::table('return_orders')
            ->leftJoin('load_requests', 'return_orders.load_request_id', '=', 'load_requests.id')
            ->leftJoin('users', 'return_orders.user_id', '=', 'users.id')
            ->leftJoin('employees', 'return_orders.employee_id', '=', 'employees.id')
            ->leftJoin('warehouses', 'return_orders.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('branches', 'return_orders.branch_id', '=', 'branches.id')
            ->select(
                'return_orders.id',
                'return_orders.return_no',
                'return_orders.return_date',
                'return_orders.return_type',
                'return_orders.return_purpose',
                'return_orders.status_id',
                'return_orders.total_items_count',
                'return_orders.total_quantity',
                'return_orders.total_amount',
                'return_orders.notes',
                'load_requests.request_no as load_request_no',
                'users.name as user_name',
                DB::raw("COALESCE(employees.first_name_ar || ' ' || employees.last_name_ar, '') as employee_name"),
                'warehouses.name as warehouse_name',
                'branches.name as branch_name'
            )
            ->orderByDesc('return_orders.id');

        if ($status) {
            $query->where('return_orders.status_id', $status);
        }

        if ($from) {
            $query->where('return_orders.return_date', '>=', $from);
        }

        if ($to) {
            $query->where('return_orders.return_date', '<=', $to);
        }

        if ($employee) {
            $query->where('return_orders.employee_id', $employee);
        }

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->warn('No return orders found.');
            return 0;
        }

        $totalQty = $orders->sum('total_quantity');
        $totalAmount = $orders->sum('total_amount');

        $this->info("Return Orders ({$orders->count()}):");
        $this->newLine();

        $rows = $orders->map(fn($o) => [
            $o->id,
            $o->return_no ?? '-',
            $o->return_date ?? '-',
            $o->return_type ?? '-',
            $o->status_id ?? '-',
            $o->employee_name ?? $o->user_name ?? '-',
            $o->warehouse_name ?? '-',
            $o->load_request_no ?? '-',
            number_format($o->total_items_count ?? 0),
            number_format($o->total_quantity ?? 0, 2),
            number_format($o->total_amount ?? 0, 2),
        ])->toArray();

        $this->table(
            ['ID', 'Return No', 'Date', 'Type', 'Status', 'Employee', 'Warehouse', 'Load Req', 'Items', 'Qty', 'Amount'],
            $rows
        );

        $this->newLine();
        $this->info("Total: Qty = " . number_format($totalQty, 2) . " | Amount = " . number_format($totalAmount, 2));

        if ($showItems) {
            $this->newLine();
            foreach ($orders as $order) {
                $this->showOrderItems($order->id);
            }
        }

        return 0;
    }

    protected function showSingleOrder(int $orderId): int
    {
        $order = DB::table('return_orders')
            ->leftJoin('load_requests', 'return_orders.load_request_id', '=', 'load_requests.id')
            ->leftJoin('users', 'return_orders.user_id', '=', 'users.id')
            ->leftJoin('employees', 'return_orders.employee_id', '=', 'employees.id')
            ->leftJoin('warehouses', 'return_orders.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('branches', 'return_orders.branch_id', '=', 'branches.id')
            ->select('return_orders.*', 'load_requests.request_no as load_request_no',
                'users.name as user_name',
                DB::raw("COALESCE(employees.first_name_ar || ' ' || employees.last_name_ar, '') as employee_name"),
                'warehouses.name as warehouse_name', 'branches.name as branch_name')
            ->where('return_orders.id', $orderId)
            ->first();

        if (!$order) {
            $this->error("Return order #{$orderId} not found.");
            return 1;
        }

        $this->info("=== Return Order: {$order->return_no} ===");
        $this->newLine();

        $details = [
            ['ID', $order->id],
            ['Return No', $order->return_no ?? '-'],
            ['Date', $order->return_date ?? '-'],
            ['Type', $order->return_type ?? '-'],
            ['Purpose', $order->return_purpose ?? '-'],
            ['Status', $order->status_id ?? '-'],
            ['Employee', $order->employee_name ?? $order->user_name ?? '-'],
            ['Warehouse', $order->warehouse_name ?? '-'],
            ['Branch', $order->branch_name ?? '-'],
            ['Load Request', $order->load_request_no ?? '-'],
            ['Total Items', number_format($order->total_items_count ?? 0)],
            ['Total Qty', number_format($order->total_quantity ?? 0, 2)],
            ['Total Amount', number_format($order->total_amount ?? 0, 2)],
            ['Notes', $order->notes ?? '-'],
        ];

        $this->table(['Field', 'Value'], $details);

        $this->showOrderItems($orderId);

        return 0;
    }

    protected function showOrderItems(int $orderId): void
    {
        $items = DB::table('return_order_items')
            ->leftJoin('items', 'return_order_items.item_id', '=', 'items.id')
            ->leftJoin('units', 'return_order_items.item_unit_id', '=', 'units.id')
            ->leftJoin('return_reasons', 'return_order_items.return_reason_id', '=', 'return_reasons.id')
            ->where('return_order_items.return_order_id', $orderId)
            ->select(
                'return_order_items.id',
                'items.name as item_name',
                'units.name as unit_name',
                'return_order_items.returned_quantity',
                'return_order_items.sold_quantity',
                'return_order_items.loaded_qty',
                'return_order_items.sales_price',
                'return_order_items.line_total',
                'return_reasons.name as reason_name',
                'return_order_items.return_condition',
                'return_order_items.notes'
            )
            ->get();

        if ($items->isEmpty()) {
            $this->warn("Order #{$orderId} has no items.");
            return;
        }

        $order = DB::table('return_orders')->where('id', $orderId)->first();
        $this->newLine();
        $orderNo = $order->return_no ?? "#{$orderId}";
        $this->info("Items for {$orderNo} ({$items->count()} items):");

        $rows = $items->map(fn($i) => [
            $i->id,
            $i->item_name ?? '-',
            $i->unit_name ?? '-',
            number_format($i->returned_quantity ?? 0, 2),
            number_format($i->sold_quantity ?? 0, 2),
            number_format($i->loaded_qty ?? 0, 2),
            number_format($i->sales_price ?? 0, 2),
            number_format($i->line_total ?? 0, 2),
            $i->reason_name ?? '-',
            $i->return_condition ?? '-',
            $i->notes ?? '-',
        ])->toArray();

        $this->table(
            ['ID', 'Item', 'Unit', 'Returned Qty', 'Sold Qty', 'Loaded', 'Price', 'Total', 'Reason', 'Condition', 'Notes'],
            $rows
        );
    }
}
