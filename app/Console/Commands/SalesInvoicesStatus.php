<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SalesInvoicesStatus extends Command
{
    protected $signature = 'sales-invoices:status {--limit=50 : Number of invoices to show} {--days=30 : Show invoices from the last N days}';
    protected $description = 'Show sales invoices data (recent, totals, sync status)';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $days = (int) $this->option('days');
        $dateFrom = now()->subDays($days)->toDateString();

        $this->info("=== Recent Sales Invoices ===");
        $this->newLine();

        $invoices = DB::table('sales_invoices')
            ->leftJoin('customers', 'sales_invoices.customer_id', '=', 'customers.id')
            ->leftJoin('users', 'sales_invoices.sales_rep_id', '=', 'users.id')
            ->whereNull('sales_invoices.deleted_at')
            ->where('sales_invoices.invoice_date', '>=', $dateFrom)
            ->select(
                'sales_invoices.id',
                'sales_invoices.invoice_no',
                'sales_invoices.invoice_date',
                'sales_invoices.status',
                'sales_invoices.sync_status',
                'customers.name_ar as customer_name',
                'users.name as rep_name',
                'sales_invoices.subtotal',
                'sales_invoices.net_total',
                'sales_invoices.paid_amount',
                'sales_invoices.remaining_amount'
            )
            ->orderByDesc('sales_invoices.id')
            ->limit($limit)
            ->get();

        if ($invoices->isEmpty()) {
            $this->warn("No invoices found in the last {$days} days.");
        } else {
            $rows = $invoices->map(fn ($i) => [
                $i->id,
                $i->invoice_no ?? '-',
                $i->invoice_date ?? '-',
                $i->status ?? '-',
                $i->sync_status ?? '-',
                $i->customer_name ?? '-',
                $i->rep_name ?? '-',
                number_format($i->subtotal ?? 0, 2),
                number_format($i->net_total ?? 0, 2),
                number_format($i->paid_amount ?? 0, 2),
                number_format($i->remaining_amount ?? 0, 2),
            ])->toArray();

            $this->table(
                ['ID', 'Invoice No', 'Date', 'Status', 'Sync', 'Customer', 'Rep', 'Subtotal', 'Net', 'Paid', 'Remaining'],
                $rows
            );
        }

        $this->newLine();
        $this->info('=== Totals (last ' . $days . ' days) ===');
        $this->newLine();

        $totals = DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->where('invoice_date', '>=', $dateFrom)
            ->selectRaw(
                'count(*) as invoices,
                 coalesce(sum(subtotal),0) as subtotal,
                 coalesce(sum(net_total),0) as net_total,
                 coalesce(sum(paid_amount),0) as paid,
                 coalesce(sum(remaining_amount),0) as remaining'
            )
            ->first();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Invoices', $totals->invoices],
                ['Subtotal', number_format($totals->subtotal, 2)],
                ['Net Total', number_format($totals->net_total, 2)],
                ['Paid', number_format($totals->paid, 2)],
                ['Remaining', number_format($totals->remaining, 2)],
            ]
        );

        $this->newLine();
        $this->info('=== Invoices by Status ===');
        $this->newLine();

        $statusCounts = DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->where('invoice_date', '>=', $dateFrom)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        if ($statusCounts->isNotEmpty()) {
            $this->table(
                ['Status', 'Count'],
                $statusCounts->map(fn ($s) => [$s->status ?? 'unknown', $s->total])->toArray()
            );
        }

        $this->newLine();
        $this->info('=== Invoices by Sync Status ===');
        $this->newLine();

        $syncCounts = DB::table('sales_invoices')
            ->whereNull('deleted_at')
            ->where('invoice_date', '>=', $dateFrom)
            ->selectRaw('sync_status, count(*) as total')
            ->groupBy('sync_status')
            ->get();

        if ($syncCounts->isNotEmpty()) {
            $this->table(
                ['Sync Status', 'Count'],
                $syncCounts->map(fn ($s) => [$s->sync_status ?? 'unknown', $s->total])->toArray()
            );
        }

        return 0;
    }
}
