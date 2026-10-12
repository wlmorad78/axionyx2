<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SalesIncentivesStatus extends Command
{
    protected $signature = 'sales-incentives:status {--limit=50 : Number of incentives to show}';
    protected $description = 'Show sales incentives data (rules, conditions, rewards, usage)';

    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info('=== Sales Incentives ===');
        $this->newLine();

        $incentives = DB::table('sales_incentives')
            ->leftJoin('companies', 'sales_incentives.company_id', '=', 'companies.id')
            ->whereNull('sales_incentives.deleted_at')
            ->select(
                'sales_incentives.id',
                'sales_incentives.code',
                'sales_incentives.name_ar',
                'sales_incentives.promotion_type',
                'sales_incentives.valid_from',
                'sales_incentives.valid_to',
                'sales_incentives.is_active',
                'companies.name as company_name'
            )
            ->orderByDesc('sales_incentives.id')
            ->limit($limit)
            ->get();

        if ($incentives->isEmpty()) {
            $this->warn('No sales incentives found.');
        } else {
            $conditionCounts = DB::table('sales_incentive_conditions')
                ->whereNull('deleted_at')
                ->selectRaw('sales_incentive_id, count(*) as total')
                ->groupBy('sales_incentive_id')
                ->pluck('total', 'sales_incentive_id');

            $rewardCounts = DB::table('sales_incentive_rewards')
                ->whereNull('deleted_at')
                ->selectRaw('sales_incentive_id, count(*) as total')
                ->groupBy('sales_incentive_id')
                ->pluck('total', 'sales_incentive_id');

            $usage = DB::table('sales_invoice_incentives')
                ->whereNull('deleted_at')
                ->selectRaw('sales_incentive_id, count(*) as invoices, coalesce(sum(benefit_amount),0) as benefit')
                ->groupBy('sales_incentive_id')
                ->get()
                ->keyBy('sales_incentive_id');

            $rows = $incentives->map(function ($i) use ($conditionCounts, $rewardCounts, $usage) {
                $u = $usage->get($i->id);

                return [
                    $i->id,
                    $i->code ?? '-',
                    $i->name_ar ?? '-',
                    $i->promotion_type ?? '-',
                    $i->valid_from ?? '-',
                    $i->valid_to ?? '-',
                    $i->is_active ? 'Yes' : 'No',
                    $conditionCounts[$i->id] ?? 0,
                    $rewardCounts[$i->id] ?? 0,
                    $u->invoices ?? 0,
                    number_format($u->benefit ?? 0, 2),
                    $i->company_name ?? '-',
                ];
            })->toArray();

            $this->table(
                ['ID', 'Code', 'Name', 'Type', 'From', 'To', 'Active', 'Conds', 'Rewards', 'Invoices', 'Benefit', 'Company'],
                $rows
            );
        }

        $this->newLine();
        $this->info('=== Incentives by Status ===');
        $this->newLine();

        $statusCounts = DB::table('sales_incentives')
            ->whereNull('deleted_at')
            ->selectRaw('is_active, count(*) as total')
            ->groupBy('is_active')
            ->get();

        if ($statusCounts->isNotEmpty()) {
            $this->table(
                ['Active', 'Count'],
                $statusCounts->map(fn ($s) => [$s->is_active ? 'Active' : 'Inactive', $s->total])->toArray()
            );
        }

        $this->newLine();
        $this->info('=== Incentive Usage on Invoices ===');
        $this->newLine();

        $usageTotals = DB::table('sales_invoice_incentives')
            ->whereNull('deleted_at')
            ->selectRaw(
                'incentive_type, count(*) as invoices, coalesce(sum(benefit_amount),0) as benefit'
            )
            ->groupBy('incentive_type')
            ->get();

        if ($usageTotals->isEmpty()) {
            $this->info('No incentive usage recorded on invoices.');
        } else {
            $this->table(
                ['Type', 'Invoices', 'Benefit'],
                $usageTotals->map(fn ($u) => [
                    $u->incentive_type ?? '-',
                    $u->invoices,
                    number_format($u->benefit, 2),
                ])->toArray()
            );
        }

        $this->newLine();
        $this->info('=== Missing Rewards / Conditions ===');
        $this->newLine();

        $broken = DB::table('sales_incentives')
            ->whereNull('sales_incentives.deleted_at')
            ->leftJoinSub(
                DB::table('sales_incentive_conditions')->whereNull('deleted_at')
                    ->selectRaw('sales_incentive_id, count(*) as c')
                    ->groupBy('sales_incentive_id'),
                'cond',
                'cond.sales_incentive_id',
                'sales_incentives.id'
            )
            ->leftJoinSub(
                DB::table('sales_incentive_rewards')->whereNull('deleted_at')
                    ->selectRaw('sales_incentive_id, count(*) as r')
                    ->groupBy('sales_incentive_id'),
                'rew',
                'rew.sales_incentive_id',
                'sales_incentives.id'
            )
            ->where(fn ($q) => $q->whereNull('cond.c')->orWhereNull('rew.r'))
            ->select('sales_incentives.id', 'sales_incentives.code', 'sales_incentives.name_ar')
            ->get();

        if ($broken->isEmpty()) {
            $this->info('All incentives have conditions and rewards. Good!');
        } else {
            $this->warn('Found ' . $broken->count() . ' incentive(s) missing conditions or rewards:');
            $this->table(
                ['ID', 'Code', 'Name'],
                $broken->map(fn ($b) => [$b->id, $b->code, $b->name_ar])->toArray()
            );
        }

        return 0;
    }
}
