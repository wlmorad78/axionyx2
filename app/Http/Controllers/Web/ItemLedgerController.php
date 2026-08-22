<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemLedgerController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Item Ledger
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Ledger" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\InventoryTransactionItem;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemLedgerController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Ledger) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = session('company_id') ?? $user?->company_id;
        $tab = $request->tab ?? 'all';

        $items = Item::where('company_id', $companyId)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $reps = Employee::where('company_id', $companyId)->orderBy('first_name_ar')->get(['id', 'first_name_ar', 'last_name_ar']);
        $warehouses = Warehouse::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $selectedItem = null;

        if ($request->item_id) {
            $selectedItem = Item::find($request->item_id);
        }

        $movements = collect();
        $stats = $this->emptyStats();
        $repBalances = collect();

        if ($request->item_id) {
            $movements = $this->getMovements($request, $companyId);
            $stats = $this->calculateStats($movements);
            $repBalances = $this->calculateRepBalances($movements);
        }

        $filteredMovements = $this->filterByTab($movements, $tab);

        return view('item-ledger.index', compact(
            'items', 'reps', 'warehouses', 'selectedItem',
            'movements', 'filteredMovements', 'stats', 'repBalances', 'tab'
        ));
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Item Ledger) حسب الطلب.
     */
    private function getMovements(Request $request, ?int $companyId): \Illuminate\Support\Collection
    {
        $itemId = $request->item_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $repId = $request->rep_id;
        $warehouseId = $request->warehouse_id;

        $rows = DB::table('inventory_transaction_items')
            ->join('inventory_transactions', 'inventory_transaction_items.inventory_transaction_id', '=', 'inventory_transactions.id')
            ->join('inventory_transaction_types', 'inventory_transactions.transaction_type_id', '=', 'inventory_transaction_types.id')
            ->leftJoin('items', 'inventory_transaction_items.item_id', '=', 'items.id')
            ->where('inventory_transactions.status', 'posted')
            ->where('inventory_transactions.company_id', $companyId)
            ->where('inventory_transaction_items.item_id', $itemId)
            ->when($dateFrom, fn($q, $v) => $q->where('inventory_transactions.transaction_date', '>=', $v))
            ->when($dateTo, fn($q, $v) => $q->where('inventory_transactions.transaction_date', '<=', $v))
            ->when($repId, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('inventory_transaction_items.from_location_type', 'rep')
                  ->where('inventory_transaction_items.from_location_id', $v)
                  ->orWhere(function($q) use ($v) {
                      $q->where('inventory_transaction_items.to_location_type', 'rep')
                        ->where('inventory_transaction_items.to_location_id', $v);
                  });
            }))
            ->when($warehouseId, fn($q, $v) => $q->where(function($q) use ($v) {
                $q->where('inventory_transaction_items.from_location_type', 'warehouse')
                  ->where('inventory_transaction_items.from_location_id', $v)
                  ->orWhere(function($q) use ($v) {
                      $q->where('inventory_transaction_items.to_location_type', 'warehouse')
                        ->where('inventory_transaction_items.to_location_id', $v);
                  })
                  ->orWhere('inventory_transactions.warehouse_id', $v);
            }))
            ->select([
                'inventory_transaction_items.id',
                'inventory_transaction_items.item_id',
                'inventory_transaction_items.qty',
                'inventory_transaction_items.unit_cost',
                'inventory_transaction_items.total_cost',
                'inventory_transaction_items.from_location_type',
                'inventory_transaction_items.from_location_id',
                'inventory_transaction_items.to_location_type',
                'inventory_transaction_items.to_location_id',
                'inventory_transactions.id as transaction_id',
                'inventory_transactions.transaction_date',
                'inventory_transactions.transaction_no',
                'inventory_transactions.transaction_type_id',
                'inventory_transactions.reference_type',
                'inventory_transactions.reference_id',
                'inventory_transactions.warehouse_id',
                'inventory_transactions.notes as txn_notes',
                'inventory_transaction_types.code as txn_type_code',
                'inventory_transaction_types.name as txn_type_name',
                'items.name_ar as item_name',
            ])
            ->orderBy('inventory_transactions.transaction_date')
            ->orderBy('inventory_transactions.id')
            ->get();

        return $this->hydrateMovements($rows);
    }

    /**
     * دالة معالجة: hydrateMovements — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function hydrateMovements($rows): \Illuminate\Support\Collection
    {
        if ($rows->isEmpty()) return collect();

        $locationCache = [
            'warehouse' => [],
            'rep' => [],
            'customer' => [],
            'supplier' => [],
            'vehicle' => [],
        ];

        $refCache = [];

        return $rows->map(function ($row) use (&$locationCache, &$refCache) {
            $row->movement_type = $this->classifyMovement($row);
            $row->in_qty = $row->qty > 0 ? $row->qty : 0;
            $row->out_qty = $row->qty < 0 ? abs($row->qty) : 0;

            $row->from_name = $this->resolveFromName($row, $locationCache, $refCache);
            $row->to_name = $this->resolveToName($row, $locationCache, $refCache);
            $row->ref_number = $this->resolveRefNumber($row);

            return $row;
        });
    }

    /**
     * دالة معالجة: classifyMovement — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function classifyMovement($row): string
    {
        if ($row->from_location_type && $row->to_location_type) {
            return match (true) {
                $row->from_location_type === 'supplier' => 'purchase',
                $row->to_location_type === 'supplier' => 'purchase_return',
                $row->from_location_type === 'warehouse' && $row->to_location_type === 'rep' => 'load',
                $row->from_location_type === 'rep' && $row->to_location_type === 'warehouse' => 'return',
                $row->from_location_type === 'rep' && $row->to_location_type === 'customer' => 'sale',
                $row->from_location_type === 'customer' && $row->to_location_type === 'rep' => 'return',
                $row->from_location_type === 'rep' && $row->to_location_type === 'rep' => 'transfer_rep',
                $row->from_location_type === 'warehouse' && $row->to_location_type === 'warehouse' => 'transfer_wh',
                default => 'other',
            };
        }

        return match ($row->txn_type_code) {
            'PURCHASE_RECEIPT' => 'purchase',
            'ISSUE_ORDER' => 'load',
            'SALES_INVOICE' => 'sale',
            'SALES_RETURN' => 'return',
            'PURCHASE_RETURN' => 'purchase_return',
            'RETURN' => 'return',
            'WAREHOUSE_TRANSFER_IN' => 'transfer_wh_in',
            'WAREHOUSE_TRANSFER_OUT' => 'transfer_wh_out',
            'REP_SALE' => 'sale',
            'REP_RETURN' => 'return',
            'TRANSFER_TO_REP' => 'load',
            'TRANSFER_FROM_REP' => 'unload',
            default => 'other',
        };
    }

    /**
     * دالة معالجة: resolveFromName — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function resolveFromName($row, array &$locationCache, array &$refCache): string
    {
        if ($row->from_location_type && $row->from_location_id) {
            return $this->resolveLocationName($row->from_location_type, $row->from_location_id, $locationCache);
        }

        return $this->resolveFromRef($row, $refCache, 'from');
    }

    /**
     * دالة معالجة: resolveToName — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function resolveToName($row, array &$locationCache, array &$refCache): string
    {
        if ($row->to_location_type && $row->to_location_id) {
            return $this->resolveLocationName($row->to_location_type, $row->to_location_id, $locationCache);
        }

        return $this->resolveFromRef($row, $refCache, 'to');
    }

    /**
     * دالة معالجة: resolveLocationName — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function resolveLocationName(string $type, int $id, array &$cache): string
    {
        if (!isset($cache[$type])) $cache[$type] = [];

        if (!isset($cache[$type][$id])) {
            $cache[$type][$id] = match ($type) {
                'warehouse' => Warehouse::find($id)?->name ?? "مخزن #$id",
                'rep' => Employee::find($id)?->full_name_ar ?? "مندوب #$id",
                'customer' => Customer::find($id)?->name ?? "عميل #$id",
                'supplier' => Supplier::find($id)?->name ?? "مورد #$id",
                'vehicle' => "مركبة #$id",
                default => "#$type #$id",
            };
        }

        return $cache[$type][$id];
    }

    /**
     * دالة معالجة: resolveFromRef — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function resolveFromRef($row, array &$refCache, string $dir): string
    {
        $refKey = $row->reference_type . '#' . $row->reference_id;
        if (!isset($refCache[$refKey])) {
            $refCache[$refKey] = $this->loadRefNames($row);
        }
        return $refCache[$refKey][$dir] ?? '—';
    }

    /**
     * دالة معالجة: loadRefNames — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function loadRefNames($row): array
    {
        $names = ['from' => '—', 'to' => '—'];

        if (!$row->reference_type || !$row->reference_id) {
            if ($row->warehouse_id) {
                $wh = Warehouse::find($row->warehouse_id);
                $names['from'] = $wh?->name ?? "مخزن #{$row->warehouse_id}";
            }
            return $names;
        }

        $refType = $row->reference_type;
        $refId = $row->reference_id;

        if (str_contains($refType, 'PurchaseInvoice') || str_contains($refType, 'PurchaseReceipt')) {
            $ref = $refType::find($refId);
            $names['from'] = $ref?->supplier?->name ?? 'مورد';
            $names['to'] = $row->warehouse_id
                ? (Warehouse::find($row->warehouse_id)?->name ?? "مخزن #{$row->warehouse_id}")
                : 'المستودع';
        } elseif (str_contains($refType, 'IssueOrder')) {
            $ref = $refType::find($refId);
            $names['from'] = $row->warehouse_id
                ? (Warehouse::find($row->warehouse_id)?->name ?? "مخزن #{$row->warehouse_id}")
                : 'المستودع';
            $names['to'] = $ref?->employee?->full_name_ar ?? 'مندوب';
        } elseif (str_contains($refType, 'SalesInvoice')) {
            $ref = $refType::find($refId);
            $names['from'] = $row->warehouse_id
                ? (Warehouse::find($row->warehouse_id)?->name ?? "مخزن #{$row->warehouse_id}")
                : 'المستودع';
            $names['to'] = $ref?->customer?->name ?? 'عميل';
        } elseif (str_contains($refType, 'ReturnOrder')) {
            $ref = $refType::find($refId);
            $names['from'] = $ref?->employee?->full_name_ar ?? 'مندوب';
            $names['to'] = $row->warehouse_id
                ? (Warehouse::find($row->warehouse_id)?->name ?? "مخزن #{$row->warehouse_id}")
                : 'المستودع';
        } else {
            if ($row->warehouse_id) {
                $wh = Warehouse::find($row->warehouse_id);
                $names['from'] = $wh?->name ?? "مخزن #{$row->warehouse_id}";
            }
        }

        return $names;
    }

    /**
     * دالة معالجة: resolveRefNumber — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function resolveRefNumber($row): string
    {
        if (!$row->reference_type || !$row->reference_id) return '';

        try {
            $ref = $row->reference_type::find($row->reference_id);
            if (!$ref) return '';

            $field = match (true) {
                str_contains($row->reference_type, 'SalesInvoice') => 'invoice_no',
                str_contains($row->reference_type, 'IssueOrder') => 'issue_no',
                str_contains($row->reference_type, 'ReturnOrder') => 'return_no',
                str_contains($row->reference_type, 'Purchase') => 'receipt_no',
                default => null,
            };

            return $field && isset($ref->{$field}) ? $ref->{$field} : "#{$row->reference_id}";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * دالة معالجة: emptyStats — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function emptyStats(): array
    {
        return [
            'total_purchase' => 0,
            'total_load' => 0,
            'total_sale' => 0,
            'total_return' => 0,
            'total_unload' => 0,
            'current_balance' => 0,
        ];
    }

    /**
     * حساب / تلخيص بيانات (Item Ledger) وإرجاع النتيجة.
     */
    private function calculateStats($movements): array
    {
        $stats = $this->emptyStats();

        foreach ($movements as $m) {
            $qty = (float) $m->qty;
            $stats['current_balance'] += $qty;

            switch ($m->movement_type) {
                case 'purchase':
                case 'purchase_return':
                    $stats['total_purchase'] += abs($qty);
                    break;
                case 'load':
                    $stats['total_load'] += abs($qty);
                    break;
                case 'sale':
                    $stats['total_sale'] += abs($qty);
                    break;
                case 'return':
                    $stats['total_return'] += abs($qty);
                    break;
                case 'unload':
                    $stats['total_unload'] += abs($qty);
                    break;
            }
        }

        return $stats;
    }

    /**
     * حساب / تلخيص بيانات (Item Ledger) وإرجاع النتيجة.
     */
    private function calculateRepBalances($movements): \Illuminate\Support\Collection
    {
        $repMap = [];

        foreach ($movements as $m) {
            $qty = (float) $m->qty;

            if ($m->from_location_type === 'rep') {
                $repId = (int) $m->from_location_id;
                if (!isset($repMap[$repId])) {
                    $repMap[$repId] = ['rep_id' => $repId, 'rep_name' => '', 'loaded' => 0, 'sold' => 0, 'returned' => 0, 'unloaded' => 0, 'balance' => 0];
                }
                $repMap[$repId]['balance'] -= $qty;
                if ($qty < 0) {
                    $repMap[$repId]['sold'] += abs($qty);
                }
            }

            if ($m->to_location_type === 'rep') {
                $repId = (int) $m->to_location_id;
                if (!isset($repMap[$repId])) {
                    $repMap[$repId] = ['rep_id' => $repId, 'rep_name' => '', 'loaded' => 0, 'sold' => 0, 'returned' => 0, 'unloaded' => 0, 'balance' => 0];
                }
                $repMap[$repId]['balance'] += $qty;
                if ($qty > 0) {
                    if ($m->movement_type === 'load') $repMap[$repId]['loaded'] += $qty;
                    if ($m->movement_type === 'return') $repMap[$repId]['returned'] += $qty;
                }
            }

            // Also check old system: ISSUE_ORDER means load to rep
            if ($m->txn_type_code === 'ISSUE_ORDER' && $m->from_location_type !== 'warehouse') {
                // For old data, we'll try to get the rep from the reference
                if ($m->reference_type && str_contains($m->reference_type, 'IssueOrder')) {
                    try {
                        $ref = $m->reference_type::find($m->reference_id);
                        if ($ref && $ref->employee_id) {
                            $eid = (int) $ref->employee_id;
                            if (!isset($repMap[$eid])) {
                                $repMap[$eid] = ['rep_id' => $eid, 'rep_name' => '', 'loaded' => 0, 'sold' => 0, 'returned' => 0, 'unloaded' => 0, 'balance' => 0];
                            }
                            $repMap[$eid]['balance'] += abs((float) $m->qty);
                            $repMap[$eid]['loaded'] += abs((float) $m->qty);
                        }
                    } catch (\Exception $e) {}
                }
            }
        }

        // Resolve rep names
        $ids = array_keys($repMap);
        if (!empty($ids)) {
            $emps = Employee::whereIn('id', $ids)->get()->keyBy('id');
            foreach ($repMap as $id => &$data) {
                $data['rep_name'] = $emps->get($id)?->full_name_ar ?? "مندوب #$id";
            }
        }

        return collect(array_values($repMap));
    }

    /**
     * دالة معالجة: repDrawer — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    public function repDrawer(Request $request, int $repId)
    {
        $user = auth()->user();
        $companyId = session('company_id') ?? $user?->company_id;
        $itemId = $request->item_id;

        $rep = Employee::find($repId);
        if (!$rep) return response()->json(['error' => 'مندوب غير موجود'], 404);

        $movements = DB::table('inventory_transaction_items')
            ->join('inventory_transactions', 'inventory_transaction_items.inventory_transaction_id', '=', 'inventory_transactions.id')
            ->join('inventory_transaction_types', 'inventory_transactions.transaction_type_id', '=', 'inventory_transaction_types.id')
            ->where('inventory_transactions.status', 'posted')
            ->where('inventory_transactions.company_id', $companyId)
            ->where(function ($q) use ($repId) {
                $q->where(function ($q2) use ($repId) {
                    $q2->where('inventory_transaction_items.from_location_type', 'rep')
                       ->where('inventory_transaction_items.from_location_id', $repId);
                })->orWhere(function ($q2) use ($repId) {
                    $q2->where('inventory_transaction_items.to_location_type', 'rep')
                       ->where('inventory_transaction_items.to_location_id', $repId);
                });
            })
            ->when($itemId, fn($q, $v) => $q->where('inventory_transaction_items.item_id', $v))
            ->select([
                'inventory_transaction_items.*',
                'inventory_transactions.transaction_date',
                'inventory_transactions.transaction_no',
                'inventory_transactions.reference_type',
                'inventory_transactions.reference_id',
                'inventory_transaction_types.code as txn_type_code',
                'inventory_transaction_types.name as txn_type_name',
            ])
            ->orderBy('inventory_transactions.transaction_date')
            ->orderBy('inventory_transactions.id')
            ->get();

        $hydrated = $this->hydrateMovements($movements);

        $balance = 0;
        foreach ($hydrated as $m) {
            $balance += (float) $m->qty;
        }

        return view('item-ledger.rep-drawer', compact('rep', 'hydrated', 'balance', 'itemId'));
    }

    /**
     * دالة معالجة: filterByTab — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Item Ledger).
     */
    private function filterByTab($movements, string $tab): \Illuminate\Support\Collection
    {
        if ($tab === 'all') return $movements;

        $typeMap = [
            'purchases' => ['purchase', 'purchase_return'],
            'loads' => ['load'],
            'sales' => ['sale'],
            'returns' => ['return'],
            'unloads' => ['unload'],
        ];

        $allowed = $typeMap[$tab] ?? [];

        return $movements->filter(fn($m) => in_array($m->movement_type, $allowed));
    }
}
