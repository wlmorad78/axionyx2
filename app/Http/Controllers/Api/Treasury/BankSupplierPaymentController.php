<?php
/**
 * =====================================================================
 * متحكم (Controller): BankSupplierPaymentController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Bank Supplier Payment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Supplier Payment" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\BankSupplierPayment;
use App\Models\BankAccount;
use App\Models\Suppliers\SupplierLedger;
use App\Models\Purchase\PurchaseInvoice;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankSupplierPaymentController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Supplier Payment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['bankAccount', 'supplier'];
        $query = BankSupplierPayment::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('payment_no', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%")
                    ->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Bank Supplier Payment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('bank_supplier_payment', 'store'));

        if (empty($data['payment_no'])) {
            $data['payment_no'] = self::generateNextCode();
        }

        $payment = DB::transaction(function () use ($data) {
            $payment = BankSupplierPayment::create($data);

            if (($data['status'] ?? 'draft') === 'completed') {
                self::executePayment($payment);
            }

            return $payment;
        });

        return response()->json($payment->load(['bankAccount', 'supplier']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Supplier Payment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankSupplierPayment $bankSupplierPayment)
    {
        return $bankSupplierPayment->load([
            'bankAccount', 'supplier', 'purchaseInvoice', 'company', 'branch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Bank Supplier Payment) بناءً على المعرّف.
     */
    public function update(Request $request, BankSupplierPayment $bankSupplierPayment)
    {
        $data = $request->validate(ValidationRules::for('bank_supplier_payment', 'update', $bankSupplierPayment));
        $bankSupplierPayment->update($data);
        return response()->json($bankSupplierPayment);
    }

    /**
     * حذف سجل من (Bank Supplier Payment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankSupplierPayment $bankSupplierPayment)
    {
        DB::transaction(function () use ($bankSupplierPayment) {
            if ($bankSupplierPayment->status === 'completed') {
                self::reversePayment($bankSupplierPayment);
            }
            $bankSupplierPayment->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Bank Supplier Payment).
     */
    public function approve(BankSupplierPayment $bankSupplierPayment)
    {
        if ($bankSupplierPayment->status !== 'draft') {
            return response()->json(['message' => 'Only draft payments can be approved'], 422);
        }

        DB::transaction(function () use ($bankSupplierPayment) {
            $bankSupplierPayment->update([
                'status' => 'completed',
                'approved_by' => null,
                'approved_at' => now(),
            ]);
            self::executePayment($bankSupplierPayment);
        });

        return response()->json($bankSupplierPayment->load(['bankAccount', 'supplier']));
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Bank Supplier Payment).
     */
    public function cancel(BankSupplierPayment $bankSupplierPayment)
    {
        if ($bankSupplierPayment->status === 'cancelled') {
            return response()->json(['message' => 'Payment already cancelled'], 422);
        }

        DB::transaction(function () use ($bankSupplierPayment) {
            if ($bankSupplierPayment->status === 'completed') {
                self::reversePayment($bankSupplierPayment);
            }
            $bankSupplierPayment->update(['status' => 'cancelled']);
        });

        return response()->json($bankSupplierPayment->load(['bankAccount', 'supplier']));
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Bank Supplier Payment).
     */
    public function nextCode()
    {
        return response()->json(['payment_no' => self::generateNextCode()]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Bank Supplier Payment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = BankSupplierPayment::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            $m->restore();
            if ($m->status === 'completed') {
                self::executePayment($m);
            }
        });

        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Bank Supplier Payment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $m = BankSupplierPayment::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            if ($m->status === 'completed') {
                self::reversePayment($m);
            }
            $m->forceDelete();
        });

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Bank Supplier Payment).
     */
    public function schema()
    {
        return ValidationRules::for('bank_supplier_payment', 'store');
    }

    /**
     * دالة معالجة: executePayment — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Bank Supplier Payment).
     */
    private static function executePayment(BankSupplierPayment $payment): void
    {
        $amount = (float) $payment->amount;
        if ($amount <= 0) return;

        BankAccount::where('id', $payment->bank_account_id)
            ->decrement('current_balance', $amount);

        SupplierLedger::create([
            'supplier_id' => $payment->supplier_id,
            'transaction_date' => $payment->payment_date,
            'reference_type' => BankSupplierPayment::class,
            'reference_id' => $payment->id,
            'debit' => $amount,
            'credit' => 0,
            'balance' => self::getSupplierBalance($payment->supplier_id) - $amount,
        ]);

        if ($payment->purchase_invoice_id) {
            $invoice = PurchaseInvoice::find($payment->purchase_invoice_id);
            if ($invoice) {
                $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $amount;
                $invoice->remaining_amount = max(0, $invoice->net_total - $invoice->paid_amount);
                $invoice->save();
            }
        }
    }

    /**
     * دالة معالجة: reversePayment — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Bank Supplier Payment).
     */
    private static function reversePayment(BankSupplierPayment $payment): void
    {
        $amount = (float) $payment->amount;
        if ($amount <= 0) return;

        BankAccount::where('id', $payment->bank_account_id)
            ->increment('current_balance', $amount);

        SupplierLedger::where('reference_type', BankSupplierPayment::class)
            ->where('reference_id', $payment->id)
            ->each(fn($ledger) => $ledger->forceDelete());

        if ($payment->purchase_invoice_id) {
            $invoice = PurchaseInvoice::find($payment->purchase_invoice_id);
            if ($invoice) {
                $invoice->paid_amount = max(0, ($invoice->paid_amount ?? 0) - $amount);
                $invoice->remaining_amount = $invoice->net_total - $invoice->paid_amount;
                $invoice->save();
            }
        }
    }

    /**
     * جلب / استعلام بيانات مخصصة لـ (Bank Supplier Payment) حسب الطلب.
     */
    private static function getSupplierBalance(int $supplierId): float
    {
        $last = SupplierLedger::where('supplier_id', $supplierId)
            ->orderByDesc('id')
            ->first();
        return $last ? (float) $last->balance : 0;
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Bank Supplier Payment).
     */
    private static function generateNextCode(): string
    {
        $last = BankSupplierPayment::withTrashed()
            ->orderByRaw("CAST(SUBSTR(payment_no, 4) AS INTEGER) DESC")
            ->first();
        if (!$last) return 'BP-00001';
        $num = 1;
        if (preg_match('/^BP-(\d+)$/', $last->payment_no, $m)) {
            $num = intval($m[1]) + 1;
        }
        return 'BP-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
