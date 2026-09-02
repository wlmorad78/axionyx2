<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReturnOrdersSyncStatus extends Command
{
    protected $signature = 'return-orders:sync-status {--limit=50 : Number of orders to show}';
    protected $description = 'Show return orders sync status (pending, failed, synced)';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info('=== Return Orders in Server Database ===');
        $this->newLine();

        $orders = DB::table('return_orders')
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
            ->orderByDesc('return_orders.id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('No return orders found in server database.');
        } else {
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
        }

        $this->newLine();
        $this->info('=== Return Orders by Status ===');
        $this->newLine();

        $statusCounts = DB::table('return_orders')
            ->select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get();

        if ($statusCounts->isNotEmpty()) {
            $statusRows = $statusCounts->map(fn($s) => [
                $s->status_id ?? 'unknown',
                $s->total,
            ])->toArray();

            $this->table(['Status', 'Count'], $statusRows);
        }

        $this->newLine();
        $this->info('=== Pending Load Requests (not closed after return) ===');
        $this->newLine();

        $pendingLoads = DB::table('load_requests')
            ->leftJoin('return_orders', 'load_requests.id', '=', 'return_orders.load_request_id')
            ->whereNotNull('return_orders.id')
            ->where('load_requests.status', '!=', 'closed')
            ->select(
                'load_requests.id',
                'load_requests.request_no',
                'load_requests.status',
                'load_requests.load_type',
                'return_orders.return_no',
                'return_orders.status_id as return_status'
            )
            ->get();

        if ($pendingLoads->isEmpty()) {
            $this->info('All load requests with returns are closed. Good!');
        } else {
            $this->warn('Found ' . $pendingLoads->count() . ' load requests with returns that are NOT closed:');
            $loadRows = $pendingLoads->map(fn($l) => [
                $l->id,
                $l->request_no,
                $l->status,
                $l->load_type,
                $l->return_no,
                $l->return_status,
            ])->toArray();

            $this->table(
                ['LR ID', 'Request No', 'Status', 'Type', 'Return No', 'Return Status'],
                $loadRows
            );
        }

        return 0;
    }
}
