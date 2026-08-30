<?php
/**
 * =====================================================================
 * متحكم (Controller): ClosingController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): Closing
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Closing" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\InventoryDailyBalance;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use App\Models\BankAccount;
use App\Services\ClosingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClosingController extends Controller
{
    /**
     * دالة معالجة: status — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    public function status(Request $request)
    {
        $request->validate(['sector' => 'required|in:inventory,finance', 'date' => 'required|date']);
        $companyId = $request->user()->company_id;
        $sector = $request->input('sector');
        $date = $request->input('date');

        return response()->json([
            'sector' => $sector,
            'date' => $date,
            'last_closed_date' => ClosingService::lastClosedDate($companyId, $sector)?->format('Y-m-d'),
            'is_closed' => ClosingService::isClosed($companyId, $sector, $date),
        ]);
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Closing).
     */
    public function close(Request $request)
    {
        $request->validate(['sector' => 'required|in:inventory,finance', 'date' => 'required|date', 'notes' => 'nullable|string']);
        $companyId = $request->user()->company_id;
        $sector = $request->input('sector');
        $date = $request->input('date');

        // لا تقفل يومًا قبل يوم مقفل لاحق
        $last = ClosingService::lastClosedDate($companyId, $sector);
        if ($last && $date < $last->format('Y-m-d')) {
            return response()->json(['message' => "لا يمكن إقفال يوم قبل آخر يوم مقفل ($last)."], 422);
        }

        $row = ClosingService::closeDay($companyId, $sector, $date, $request->user()->id, $request->input('notes'));

        return response()->json([
            'message' => 'تم إقفال اليوم بنجاح',
            'closing' => $row,
            'last_closed_date' => ClosingService::lastClosedDate($companyId, $sector)?->format('Y-m-d'),
        ]);
    }

    /**
     * دالة معالجة: reopen — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    public function reopen(Request $request)
    {
        $request->validate(['sector' => 'required|in:inventory,finance', 'date' => 'required|date']);
        $companyId = $request->user()->company_id;
        $sector = $request->input('sector');
        $date = $request->input('date');

        ClosingService::reopenDay($companyId, $sector, $date);

        return response()->json([
            'message' => 'تم فتح اليوم للتعديل. يمكنك إعادة الإقفال لاحقًا.',
            'last_closed_date' => ClosingService::lastClosedDate($companyId, $sector)?->format('Y-m-d'),
        ]);
    }

    /**
     * مراجعة قطاع المخازن: أرصدة الأصناف لنهاية اليوم + حالة الإقفال.
     */
    public function reviewInventory(Request $request)
    {
        $request->validate(['date' => 'required|date', 'warehouse_id' => 'nullable|integer']);
        $companyId = $request->user()->company_id;
        $date = $request->input('date');
        $warehouseId = $request->input('warehouse_id');

        $query = InventoryDailyBalance::with('item:id,name_ar,name_en,code')
            ->where('company_id', $companyId)
            ->where('balance_date', $date);
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        $rows = $query->orderByDesc('closing_balance')->get();

        $items = $rows->map(function ($r) {
            return [
                'item_id' => $r->item_id,
                'name' => $r->item?->name_ar ?? $r->item?->name_en ?? '',
                'code' => $r->item?->code ?? '',
                'warehouse_id' => $r->warehouse_id,
                'opening_balance' => (float) $r->opening_balance,
                'incoming_qty' => (float) $r->incoming_qty,
                'outgoing_qty' => (float) $r->outgoing_qty,
                'closing_balance' => (float) $r->closing_balance,
            ];
        });

        return response()->json([
            'date' => $date,
            'sector' => ClosingService::SECTOR_INVENTORY,
            'last_closed_date' => ClosingService::lastClosedDate($companyId, ClosingService::SECTOR_INVENTORY)?->format('Y-m-d'),
            'is_closed' => ClosingService::isClosed($companyId, ClosingService::SECTOR_INVENTORY, $date),
            'items' => $items,
        ]);
    }

    /**
     * مراجعة قطاع المالية: العملاء/الموردين/الخزن/البنوك اللي اتعامل معاهم في اليوم.
     */
    public function reviewFinance(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $companyId = $request->user()->company_id;
        $date = $request->input('date');

        $customers = $this->financeCustomers($companyId, $date);
        $suppliers = $this->financeSuppliers($companyId, $date);
        $treasuries = $this->financeTreasuries($companyId, $date);
        $banks = $this->financeBanks($companyId, $date);

        return response()->json([
            'date' => $date,
            'sector' => ClosingService::SECTOR_FINANCE,
            'last_closed_date' => ClosingService::lastClosedDate($companyId, ClosingService::SECTOR_FINANCE)?->format('Y-m-d'),
            'is_closed' => ClosingService::isClosed($companyId, ClosingService::SECTOR_FINANCE, $date),
            'customers' => $customers,
            'suppliers' => $suppliers,
            'treasuries' => $treasuries,
            'banks' => $banks,
        ]);
    }

    /**
     * دالة معالجة: financeCustomers — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    private function financeCustomers(int $companyId, string $date): array
    {
        $ledger = CustomerLedger::whereDate('transaction_date', $date)
            ->whereIn('customer_id', Customer::where('company_id', $companyId)->pluck('id')->all() ?: [0])
            ->get();

        $result = [];
        foreach ($ledger->groupBy('customer_id') as $cid => $rows) {
            $debit = (float) $rows->sum('debit');
            $credit = (float) $rows->sum('credit');
            $opening = (float) (CustomerLedger::where('customer_id', $cid)
                ->whereDate('transaction_date', '<', $date)
                ->orderByDesc('transaction_date')->orderByDesc('id')->value('balance') ?? 0);
            $closing = $opening + $debit - $credit;
            $c = Customer::find($cid);
            $result[] = [
                'id' => $cid,
                'name' => $c?->name_ar ?? $c?->name ?? '',
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'closing' => $closing,
            ];
        }

        return $result;
    }

    /**
     * دالة معالجة: financeSuppliers — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    private function financeSuppliers(int $companyId, string $date): array
    {
        $ledger = SupplierLedger::whereDate('transaction_date', $date)
            ->whereIn('supplier_id', Supplier::where('company_id', $companyId)->pluck('id')->all() ?: [0])
            ->get();

        $result = [];
        foreach ($ledger->groupBy('supplier_id') as $sid => $rows) {
            $debit = (float) $rows->sum('debit');
            $credit = (float) $rows->sum('credit');
            $opening = (float) (SupplierLedger::where('supplier_id', $sid)
                ->whereDate('transaction_date', '<', $date)
                ->orderByDesc('transaction_date')->orderByDesc('id')->value('balance') ?? 0);
            $closing = $opening + $debit - $credit;
            $s = Supplier::find($sid);
            $result[] = [
                'id' => $sid,
                'name' => $s?->name_ar ?? $s?->name ?? '',
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'closing' => $closing,
            ];
        }

        return $result;
    }

    /**
     * دالة معالجة: financeTreasuries — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    private function financeTreasuries(int $companyId, string $date): array
    {
        $treasuries = Treasury::where('company_id', $companyId)->get();
        $result = [];
        foreach ($treasuries as $t) {
            $txns = TreasuryTransaction::where('treasury_id', $t->id)
                ->whereDate('transaction_date', $date)
                ->get();
            if ($txns->isEmpty()) {
                continue;
            }
            $creditTypes = ['credit', 'income', 'transfer_in', 'opening_balance'];
            $dayCredit = (float) $txns->whereIn('type', $creditTypes)->sum('amount');
            $dayDebit = (float) $txns->whereNotIn('type', $creditTypes)->sum('amount');
            $opening = (float) $t->opening_balance
                + (float) TreasuryTransaction::where('treasury_id', $t->id)
                    ->whereDate('transaction_date', '<', $date)
                    ->whereIn('type', $creditTypes)->sum('amount')
                - (float) TreasuryTransaction::where('treasury_id', $t->id)
                    ->whereDate('transaction_date', '<', $date)
                    ->whereNotIn('type', $creditTypes)->sum('amount');
            $closing = $opening + $dayCredit - $dayDebit;
            $result[] = [
                'id' => $t->id,
                'name' => $t->name_ar ?? $t->name ?? '',
                'opening' => $opening,
                'debit' => $dayDebit,
                'credit' => $dayCredit,
                'closing' => $closing,
            ];
        }

        return $result;
    }

    /**
     * دالة معالجة: financeBanks — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Closing).
     */
    private function financeBanks(int $companyId, string $date): array
    {
        $banks = BankAccount::where('company_id', $companyId)->get();
        $result = [];
        foreach ($banks as $b) {
            // حركة اليوم من تحويلات البنك والمدفوعات الموردين من البنك
            $out = (float) DB::table('bank_supplier_payments')
                ->where('bank_account_id', $b->id)
                ->whereDate('payment_date', $date)->sum('amount');
            $transferOut = (float) DB::table('bank_transfers')
                ->where('from_bank_id', $b->id)
                ->whereDate('transfer_date', $date)->sum('amount');
            $transferIn = (float) DB::table('bank_transfers')
                ->where('to_bank_id', $b->id)
                ->whereDate('transfer_date', $date)->sum('amount');

            $dayDebit = $out + $transferOut;
            $dayCredit = $transferIn;
            if ($dayDebit == 0 && $dayCredit == 0) {
                continue; // بنك لم يُتعامل معه في اليوم
            }
            // الرصيد الافتتاحي التقريبي = الرصيد الحالي - حركة اليوم
            $opening = (float) $b->current_balance - $dayCredit + $dayDebit;
            $closing = (float) $b->current_balance;
            $result[] = [
                'id' => $b->id,
                'name' => $b->bank_name ?? ($b->account_name ?? ''),
                'opening' => $opening,
                'debit' => $dayDebit,
                'credit' => $dayCredit,
                'closing' => $closing,
            ];
        }

        return $result;
    }
}
