<?php

namespace App\Console\Commands;

use App\Models\InventoryDailyBalance;
use App\Models\InventoryOpeningBalance;
use App\Models\InventoryTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventorySnapshotCommand extends Command
{
    protected $signature = 'inventory:snapshot
        {--company= : معرّف الشركة (اختياري - كل الشركات إذا لم يُحدد)}
        {--date=YYYY-MM-DD : تاريخ واحد (افتراضي: أمس)}
        {--from=YYYY-MM-DD : بداية النطاق للإعادة الكاملة}
        {--to=YYYY-MM-DD : نهاية النطاق (افتراضي: اليوم)}
        {--force : إعادة حساب التواريخ الموجودة}';

    protected $description = 'حساب وتخزين الأرصدة اليومية للمخزون في جدول inventory_daily_balances';

    public function handle(): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $dateOpt = $this->normalizeDateOption($this->option('date'));
        $fromOpt = $this->normalizeDateOption($this->option('from'));
        $toOpt = $this->normalizeDateOption($this->option('to'));
        $force = (bool) $this->option('force');

        // تحديد نطاق التواريخ
        if ($dateOpt) {
            $dates = [CarbonDate($dateOpt)];
        } elseif ($fromOpt || $toOpt) {
            $start = $fromOpt ? CarbonDate($fromOpt) : $this->earliestDate($companyId);
            $end = $toOpt ? CarbonDate($toOpt) : CarbonDate(now()->format('Y-m-d'));
            $dates = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dates[] = $d->copy();
            }
        } else {
            // افتراضي: أمس (يصلح للجدولة اليومية)
            $dates = [CarbonDate(now()->subDay()->format('Y-m-d'))];
        }

        if (empty($dates)) {
            $this->warn('لا توجد تواريخ للحساب.');

            return 0;
        }

        $this->info('عدد التواريخ: ' . count($dates));

        // حذف الأرصدة القديمة عند استخدام --force
        if ($force) {
            foreach ($dates as $d) {
                $dateDel = $d->format('Y-m-d');
                $deleted = DB::statement(
                    "DELETE FROM inventory_daily_balances WHERE balance_date LIKE ?",
                    [$dateDel . '%']
                );
                $this->info("حذف {$dateDel}: {$deleted} صف.");
            }
        }

        $bar = $this->output->createProgressBar(count($dates));
        $bar->start();

        $totalRows = 0;
        foreach ($dates as $date) {
            $totalRows += $this->computeDate($companyId, $date, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("تم تخزين/تحديث {$totalRows} صف للأرصدة اليومية.");

        return 0;
    }

    /**
     * حساب يوم واحد لكل الشركات (أو شركة محددة) وتخزينه.
     * الرصيد الصباحي = رصيد افتتاحي + مجموع الحركات المرحلة قبل التاريخ.
     */
    protected function computeDate(?int $companyId, \Carbon\Carbon $date, bool $force): int
    {
        $dateStr = $date->format('Y-m-d');
        $companies = $companyId
            ? [$companyId]
            : DB::table('companies')->pluck('id')->all();

        $rows = 0;
        foreach ($companies as $cid) {
            // 1) الأرصدة الافتتاحية (opening_date <= date)
            $obColl = InventoryOpeningBalance::where('company_id', $cid)
                ->whereDate('opening_date', '<=', $dateStr)
                ->get();
            $obMap = []; // [warehouseKey][itemId] = qty
            foreach ($obColl as $ob) {
                $wh = $ob->warehouse_id ?: 0;
                $obMap[$wh][$ob->item_id] = ($obMap[$wh][$ob->item_id] ?? 0) + (float) $ob->qty;
            }

            // 2) الحركات قبل التاريخ (رصيد صباحي متراكم)
            $prior = InventoryTransaction::with('transactionType:id,effect,code')
                ->with('items:id,inventory_transaction_id,item_id,qty')
                ->where('company_id', $cid)
                ->where('status', 'posted')
                ->whereDate('transaction_date', '<', $dateStr)
                ->whereHas('transactionType', fn($q) => $q->whereIn('effect', ['addition', 'subtraction']))
                ->get();

            $priorMap = []; // [wh][itemId] = qty موقعة
            foreach ($prior as $txn) {
                $effect = $txn->transactionType?->effect;
                $code = $txn->transactionType?->code;
                if ($code === 'SALES_RETURN') {
                    continue;
                }
                $sign = $effect === 'addition' ? 1 : ($effect === 'subtraction' ? -1 : 0);
                if ($sign === 0) {
                    continue;
                }
                $wh = $txn->warehouse_id ?: 0;
                foreach ($txn->items as $it) {
                    $priorMap[$wh][$it->item_id] = ($priorMap[$wh][$it->item_id] ?? 0) + $sign * abs((float) $it->qty);
                }
            }

            // 3) حركات اليوم (بدون مرتجعات المبيعات)
            $day = InventoryTransaction::with('transactionType:id,effect,code')
                ->with('items:id,inventory_transaction_id,item_id,qty')
                ->where('company_id', $cid)
                ->where('status', 'posted')
                ->whereDate('transaction_date', $dateStr)
                ->whereHas('transactionType', fn($q) => $q->whereIn('effect', ['addition', 'subtraction']))
                ->get();

            $inMap = [];
            $outMap = [];
            foreach ($day as $txn) {
                $effect = $txn->transactionType?->effect;
                $code = $txn->transactionType?->code;
                $wh = $txn->warehouse_id ?: 0;
                foreach ($txn->items as $it) {
                    $q = abs((float) $it->qty);
                    if ($effect === 'addition' && $code !== 'SALES_RETURN') {
                        $inMap[$wh][$it->item_id] = ($inMap[$wh][$it->item_id] ?? 0) + $q;
                    } elseif ($effect === 'subtraction') {
                        $outMap[$wh][$it->item_id] = ($outMap[$wh][$it->item_id] ?? 0) + $q;
                    }
                }
            }

            // 4) بناء الصفوف (لكل مستودع/صنف ظهر في أي من الخرائط)
            $warehouses = array_unique(array_merge(
                array_keys($obMap),
                array_keys($priorMap),
                array_keys($inMap),
                array_keys($outMap)
            ));

            foreach ($warehouses as $wh) {
                $itemIds = array_unique(array_merge(
                    array_keys($obMap[$wh] ?? []),
                    array_keys($priorMap[$wh] ?? []),
                    array_keys($inMap[$wh] ?? []),
                    array_keys($outMap[$wh] ?? [])
                ));
                foreach ($itemIds as $itemId) {
                    $opening = (float) ($obMap[$wh][$itemId] ?? 0) + (float) ($priorMap[$wh][$itemId] ?? 0);
                    $incoming = (float) ($inMap[$wh][$itemId] ?? 0);
                    $outgoing = (float) ($outMap[$wh][$itemId] ?? 0);
                    $closing = $opening + $incoming - $outgoing;

                    if (!$force) {
                        $exists = InventoryDailyBalance::where('company_id', $cid)
                            ->where('warehouse_id', $wh)
                            ->where('item_id', $itemId)
                            ->where('balance_date', $dateStr)
                            ->exists();
                        if ($exists) {
                            continue;
                        }
                    } else {
                        // مع --force: احذف القديم ثم أضف الجديد
                        DB::statement(
                            "DELETE FROM inventory_daily_balances WHERE company_id = ? AND warehouse_id = ? AND item_id = ? AND balance_date LIKE ?",
                            [$cid, $wh, $itemId, $dateStr . '%']
                        );
                    }

                    InventoryDailyBalance::updateOrCreate(
                        [
                            'company_id'   => $cid,
                            'warehouse_id' => $wh,
                            'item_id'      => $itemId,
                            'balance_date' => $dateStr,
                        ],
                        [
                            'opening_balance' => $opening,
                            'incoming_qty'    => $incoming,
                            'outgoing_qty'    => $outgoing,
                            'closing_balance' => $closing,
                        ]
                    );
                    $rows++;
                }
            }
        }

        return $rows;
    }

    protected function normalizeDateOption($value): ?string
    {
        if ($value === null || $value === 'YYYY-MM-DD' || $value === '') {
            return null;
        }

        return $value;
    }

    protected function earliestDate(?int $companyId): \Carbon\Carbon
    {
        $ob = InventoryOpeningBalance::query();
        $tx = InventoryTransaction::query();
        if ($companyId) {
            $ob->where('company_id', $companyId);
            $tx->where('company_id', $companyId);
        }
        $d1 = $ob->min('opening_date');
        $d2 = $tx->min('transaction_date');

        $candidates = array_filter([$d1, $d2]);
        if (empty($candidates)) {
            return CarbonDate(now()->format('Y-m-d'));
        }

        return CarbonDate(min($candidates));
    }
}

if (!function_exists('CarbonDate')) {
    function CarbonDate(string $date): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
    }
}
