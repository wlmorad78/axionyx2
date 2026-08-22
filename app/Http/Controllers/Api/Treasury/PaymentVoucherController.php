<?php
/**
 * =====================================================================
 * متحكم (Controller): PaymentVoucherController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Payment Voucher
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Payment Voucher" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\PaymentVoucher;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Treasury\TreasuryTransaction;
use App\Models\Treasury\Treasury;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoucherController extends Controller
{
    /**
     * عرض قائمة سجلات (Payment Voucher) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PaymentVoucher::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('voucher_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Payment Voucher) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payment_voucher', 'store'));
        if (empty($data['voucher_no'])) {
            $data['voucher_no'] = self::generateNextCode();
        }

        $voucher = DB::transaction(function () use ($data) {
            $voucher = PaymentVoucher::create($data);

            if (!empty($data['purchase_invoice_id']) && !empty($data['amount'])) {
                $invoice = PurchaseInvoice::find($data['purchase_invoice_id']);
                if ($invoice) {
                    $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $data['amount'];
                    $invoice->remaining_amount = max(0, $invoice->net_total - $invoice->paid_amount);
                    $invoice->save();
                }
            }

            self::syncTreasury($voucher);

            return $voucher;
        });

        return response()->json($voucher->load('purchaseInvoice'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Payment Voucher) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PaymentVoucher $paymentVoucher)
    {
        return $paymentVoucher->load([
            'supplier', 'bankAccount', 'company', 'branch',
            'purchaseInvoice',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Payment Voucher) بناءً على المعرّف.
     */
    public function update(Request $request, PaymentVoucher $paymentVoucher)
    {
        $data = $request->validate(ValidationRules::for('payment_voucher', 'update', $paymentVoucher));
        $paymentVoucher->update($data);
        return response()->json($paymentVoucher);
    }

    /**
     * حذف سجل من (Payment Voucher) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PaymentVoucher $paymentVoucher)
    {
        DB::transaction(function () use ($paymentVoucher) {
            if ($paymentVoucher->purchase_invoice_id && $paymentVoucher->amount) {
                $invoice = PurchaseInvoice::find($paymentVoucher->purchase_invoice_id);
                if ($invoice) {
                    $invoice->paid_amount = max(0, ($invoice->paid_amount ?? 0) - $paymentVoucher->amount);
                    $invoice->remaining_amount = $invoice->net_total - $invoice->paid_amount;
                    $invoice->save();
                }
            }

            self::reverseTreasury($paymentVoucher);

            $paymentVoucher->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Payment Voucher).
     */
    public function nextCode()
    {
        return response()->json(['voucher_no' => self::generateNextCode()]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Payment Voucher) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = PaymentVoucher::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            $m->restore();

            if ($m->purchase_invoice_id && $m->amount) {
                $invoice = PurchaseInvoice::find($m->purchase_invoice_id);
                if ($invoice) {
                    $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $m->amount;
                    $invoice->remaining_amount = max(0, $invoice->net_total - $invoice->paid_amount);
                    $invoice->save();
                }
            }

            self::syncTreasury($m);
        });

        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Payment Voucher) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PaymentVoucher::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Payment Voucher).
     */
    public function schema()
    {
        return ValidationRules::for('payment_voucher', 'store');
    }

    /**
     * دالة معالجة: syncTreasury — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Payment Voucher).
     */
    private static function syncTreasury(PaymentVoucher $voucher): void
    {
        $amount = (float)($voucher->amount ?? 0);
        if ($amount <= 0) return;

        $treasuryId = $voucher->safe_id;
        if (!$treasuryId) {
            $mainTreasury = Treasury::where('company_id', $voucher->company_id)
                ->where('is_main', true)->where('is_active', true)->first();
            if (!$mainTreasury) return;
            $treasuryId = $mainTreasury->id;
            $voucher->update(['safe_id' => $treasuryId]);
        }

        TreasuryTransaction::create([
            'company_id' => $voucher->company_id,
            'treasury_id' => $treasuryId,
            'type' => 'debit',
            'amount' => $amount,
            'reference_type' => PaymentVoucher::class,
            'reference_id' => $voucher->id,
            'description' => "ØµØ±Ù Ø³Ù†Ø¯ ØµØ±Ù Ø±Ù‚Ù… {$voucher->voucher_no}",
            'transaction_date' => $voucher->voucher_date,
            'created_by' => $voucher->created_by,
        ]);
    }

    /**
     * دالة معالجة: reverseTreasury — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Payment Voucher).
     */
    private static function reverseTreasury(PaymentVoucher $voucher): void
    {
        TreasuryTransaction::where('reference_type', PaymentVoucher::class)
            ->where('reference_id', $voucher->id)
            ->each(fn($txn) => $txn->forceDelete());
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Payment Voucher).
     */
    private static function generateNextCode(): string
    {
        $last = PaymentVoucher::withTrashed()->orderByDesc('id')->value('voucher_no');
        if (!$last) return 'PV-00001';
        $num = (int) substr($last, 3) + 1;
        return 'PV-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
