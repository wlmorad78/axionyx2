<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowReturnOrders extends Command
{
    protected $signature = 'return-orders:show {--status= : Filter by status} {--limit=20 : Number of orders to show}';
    protected $description = 'Show return orders';

    public function handle()
    {
        $status = $this->option('status');
        $limit = (int) $this->option('limit');

        $query = DB::table('return_orders')
            ->leftJoin('load_requests', 'return_orders.load_request_id', '=', 'load_requests.id')
            ->leftJoin('users', 'return_orders.user_id', '=', 'users.id')
            ->select(
                'return_orders.id',
                'return_orders.return_no',
                'return_orders.return_date',
                'return_orders.return_type',
                'return_orders.status_id',
                'return_orders.total_quantity',
                'return_orders.total_amount',
                'load_requests.request_no as load_request_no',
                'users.name as user_name'
            )
            ->orderByDesc('return_orders.id');

        if ($status) {
            $query->where('return_orders.status_id', $status);
        }

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->warn('No return orders found.');
            return 0;
        }

        $this->info('Return Orders (' . $orders->count() . '):');
        $this->newLine();

        $rows = $orders->map(fn($o) => [
            $o->id,
            $o->return_no ?? '-',
            $o->return_date ?? '-',
            $o->return_type ?? '-',
            $o->status_id ?? '-',
            $o->load_request_no ?? '-',
            $o->user_name ?? '-',
            number_format($o->total_quantity ?? 0, 2),
            number_format($o->total_amount ?? 0, 2),
        ])->toArray();

        $this->table(
            ['ID', 'Return No', 'Date', 'Type', 'Status', 'Load Request', 'Employee', 'Qty', 'Amount'],
            $rows
        );

        return 0;
    }
}
