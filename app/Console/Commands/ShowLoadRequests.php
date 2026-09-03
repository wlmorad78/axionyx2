<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowLoadRequests extends Command
{
    protected $signature = 'load-requests:show {--status= : Filter by status} {--user= : Filter by user ID} {--limit=20 : Number of orders to show}';
    protected $description = 'Show load requests with their return status';

    public function handle()
    {
        $status = $this->option('status');
        $userId = $this->option('user');
        $limit = (int) $this->option('limit');

        $query = DB::table('load_requests')
            ->leftJoin('employees', 'load_requests.employee_id', '=', 'employees.id')
            ->leftJoin('users', 'employees.user_id', '=', 'users.id')
            ->select(
                'load_requests.id',
                'load_requests.request_no',
                'load_requests.status',
                'load_requests.load_type',
                'load_requests.request_date',
                'load_requests.created_at',
                'load_requests.total_items_count',
                'load_requests.total_quantity',
                'load_requests.total_amount',
                'load_requests.parent_load_request_id',
                'users.name as user_name',
                DB::raw('(SELECT return_no FROM return_orders WHERE load_request_id = load_requests.id ORDER BY id DESC LIMIT 1) as return_no'),
                DB::raw('(SELECT status_id FROM return_orders WHERE load_request_id = load_requests.id ORDER BY id DESC LIMIT 1) as return_status')
            )
            ->orderByDesc('load_requests.id');

        if ($status) {
            $query->where('load_requests.status', $status);
        }

        if ($userId) {
            $query->where('employees.user_id', $userId);
        }

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->warn('No load requests found.');
            return 0;
        }

        $this->info('Load Requests (' . $orders->count() . '):');
        $this->newLine();

        $rows = $orders->map(fn($o) => [
            $o->id,
            $o->request_no ?? '-',
            $o->status ?? '-',
            $o->load_type ?? '-',
            $o->request_date ?? '-',
            $o->created_at ? substr($o->created_at, 11, 8) : '-',
            $o->user_name ?? '-',
            $o->total_items_count ?? 0,
            number_format($o->total_quantity ?? 0, 2),
            number_format($o->total_amount ?? 0, 2),
            $o->parent_load_request_id ?? '-',
            $o->return_no ?? '-',
            $o->return_status ?? '-',
        ])->toArray();

        $this->table(
            ['ID', 'Request No', 'Status', 'Type', 'Date', 'Time', 'Employee', 'Items', 'Qty', 'Amount', 'Parent', 'Return No', 'Return Status'],
            $rows
        );

        return 0;
    }
}
