<?php
/**
 * =====================================================================
 * متحكم (Controller): ReceiptVoucherController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Receipt Voucher
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Receipt Voucher" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\ReceiptVoucher;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ReceiptVoucherController extends Controller
{
    /**
     * عرض قائمة سجلات (Receipt Voucher) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ReceiptVoucher::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('voucher_no', 'like', "%$s%")->orWhere('reference', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Receipt Voucher) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('receipt_voucher', 'store'));
        if (empty($data['voucher_no'])) {
            $data['voucher_no'] = self::generateNextCode();
        }
        return response()->json(ReceiptVoucher::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Receipt Voucher) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ReceiptVoucher $receiptVoucher)
    {
        return $receiptVoucher->load([
            'customer', 'bankAccount', 'company', 'branch',
            'createdByEmployee', 'approvedByEmployee',
            'payments',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Receipt Voucher) بناءً على المعرّف.
     */
    public function update(Request $request, ReceiptVoucher $receiptVoucher)
    {
        $data = $request->validate(ValidationRules::for('receipt_voucher', 'update', $receiptVoucher));
        $receiptVoucher->update($data);
        return response()->json($receiptVoucher);
    }

    /**
     * حذف سجل من (Receipt Voucher) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ReceiptVoucher $receiptVoucher)
    {
        $receiptVoucher->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Receipt Voucher).
     */
    public function nextCode()
    {
        return response()->json(['voucher_no' => self::generateNextCode()]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Receipt Voucher) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = ReceiptVoucher::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Receipt Voucher) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ReceiptVoucher::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Receipt Voucher).
     */
    public function schema()
    {
        return ValidationRules::for('receipt_voucher', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Receipt Voucher).
     */
    private static function generateNextCode(): string
    {
        $last = ReceiptVoucher::orderByDesc('id')->value('voucher_no');
        if (!$last) return 'RV-00001';
        $num = (int) substr($last, 3) + 1;
        return 'RV-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
