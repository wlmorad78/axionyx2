<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierController
 * الوحدة (Module): الموردين (Suppliers)
 * المورد (Resource): Supplier
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier" ضمن وحدة "الموردين".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Suppliers\Supplier;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Supplier::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class)->with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->supplier_group_id) {
            $query->where('supplier_group_id', $request->supplier_group_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('supplier_name', 'like', "%$s%")
                    ->orWhere('supplier_code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier', 'store'));
        return response()->json(Supplier::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Supplier $supplier)
    {
        return $supplier->load(['supplierGroup', 'contacts', 'country', 'governorate', 'city', 'district']);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier) بناءً على المعرّف.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate(ValidationRules::for('supplier', 'update', $supplier));
        $supplier->update($data);
        return response()->json($supplier);
    }

    /**
     * حذف سجل من (Supplier) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = Supplier::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Supplier) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Supplier::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier).
     */
    public function schema()
    {
        return ValidationRules::for('supplier', 'store');
    }

    /**
     * دالة معالجة: statement — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Supplier).
     */
    public function statement(Request $request, int $id)
    {
        $supplier = Supplier::withoutBranchScope()->findOrFail($id);
        $from = $request->input('from');
        $to = $request->input('to');

        $transactions = [];

        // Start with supplier table opening_balance
        $openingBalance = (float) ($supplier->opening_balance ?? 0);

        // Also aggregate from opening balance document lines linked to this supplier
        $obLines = \App\Models\OpeningBalanceDocumentLine::where('supplier_id', $id)
            ->whereHas('document', fn($q) => $q->where('status', 'posted'))
            ->get();

        foreach ($obLines as $line) {
            $lineBalance = (float) $line->credit - (float) $line->debit;
            $openingBalance += $lineBalance;
        }

        if ($from) {
            $priorInvoices = \App\Models\Purchase\PurchaseInvoice::where('supplier_id', $id)
                ->where('status', '!=', 'cancelled')
                ->where('invoice_date', '<', $from)
                ->get(['net_total']);
            foreach ($priorInvoices as $inv) {
                $openingBalance += (float) $inv->net_total;
            }

            $priorPayments = \App\Models\Treasury\PaymentVoucher::where('supplier_id', $id)
                ->where('status', '!=', 'cancelled')
                ->where('voucher_date', '<', $from)
                ->get(['amount']);
            foreach ($priorPayments as $pay) {
                $openingBalance -= (float) $pay->amount;
            }

            $priorBankPayments = \App\Models\Treasury\BankSupplierPayment::where('supplier_id', $id)
                ->where('status', '!=', 'cancelled')
                ->where('payment_date', '<', $from)
                ->get(['amount']);
            foreach ($priorBankPayments as $pay) {
                $openingBalance -= (float) $pay->amount;
            }
        }

        $invoices = \App\Models\Purchase\PurchaseInvoice::where('supplier_id', $id)
            ->where('status', '!=', 'cancelled')
            ->when($from, fn($q) => $q->where('invoice_date', '>=', $from))
            ->when($to, fn($q) => $q->where('invoice_date', '<=', $to))
            ->get(['id', 'invoice_no', 'invoice_date', 'net_total', 'paid_amount', 'remaining_amount', 'status']);

        foreach ($invoices as $inv) {
            $transactions[] = [
                'date' => $inv->invoice_date?->format('Y-m-d'),
                'type' => 'purchase_invoice',
                'reference_no' => $inv->invoice_no,
                'debit' => (float) $inv->net_total,
                'credit' => 0.0,
                'description' => 'فاتورة شراء - ' . $inv->invoice_no,
                'status' => $inv->status,
            ];
        }

        $payments = \App\Models\Treasury\PaymentVoucher::where('supplier_id', $id)
            ->where('status', '!=', 'cancelled')
            ->when($from, fn($q) => $q->where('voucher_date', '>=', $from))
            ->when($to, fn($q) => $q->where('voucher_date', '<=', $to))
            ->get(['id', 'voucher_no', 'voucher_date', 'amount', 'status']);

        foreach ($payments as $pay) {
            $transactions[] = [
                'date' => $pay->voucher_date?->format('Y-m-d'),
                'type' => 'payment_voucher',
                'reference_no' => $pay->voucher_no,
                'debit' => 0.0,
                'credit' => (float) $pay->amount,
                'description' => 'سند صرف - ' . $pay->voucher_no,
                'status' => $pay->status,
            ];
        }

        $bankPayments = \App\Models\Treasury\BankSupplierPayment::where('supplier_id', $id)
            ->where('status', '!=', 'cancelled')
            ->when($from, fn($q) => $q->where('payment_date', '>=', $from))
            ->when($to, fn($q) => $q->where('payment_date', '<=', $to))
            ->get(['id', 'payment_no', 'payment_date', 'amount', 'status']);

        foreach ($bankPayments as $pay) {
            $transactions[] = [
                'date' => $pay->payment_date?->format('Y-m-d'),
                'type' => 'bank_supplier_payment',
                'reference_no' => $pay->payment_no,
                'debit' => 0.0,
                'credit' => (float) $pay->amount,
                'description' => 'دفعة بنكية - ' . $pay->payment_no,
                'status' => $pay->status,
            ];
        }

        usort($transactions, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));

        $runningBalance = $openingBalance;
        foreach ($transactions as &$tx) {
            $runningBalance += $tx['debit'] - $tx['credit'];
            $tx['balance'] = round($runningBalance, 2);
        }
        unset($tx);

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'supplier_name' => $supplier->supplier_name,
                'supplier_code' => $supplier->supplier_code,
                'phone' => $supplier->phone,
                'opening_balance' => $openingBalance,
            ],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
            'summary' => [
                'total_debit' => round(collect($transactions)->sum('debit'), 2),
                'total_credit' => round(collect($transactions)->sum('credit'), 2),
                'final_balance' => round($runningBalance, 2),
            ],
        ]);
    }
}
