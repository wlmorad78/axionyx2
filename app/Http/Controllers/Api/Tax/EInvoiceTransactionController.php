<?php
/**
 * =====================================================================
 * متحكم (Controller): EInvoiceTransactionController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): E Invoice Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "E Invoice Transaction" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{EInvoiceTransaction};
use App\Support\ValidationRules;

class EInvoiceTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (E Invoice Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = EInvoiceTransaction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('external_reference', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (E Invoice Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('e_invoice_transaction', 'create'));
        $eInvoiceTransaction = EInvoiceTransaction::create($data);
        return response()->json($eInvoiceTransaction, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (E Invoice Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return EInvoiceTransaction::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (E Invoice Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('e_invoice_transaction', 'update', $eInvoiceTransaction));
        $eInvoiceTransaction->update($data);
        return $eInvoiceTransaction;
    }

    /**
     * حذف سجل من (E Invoice Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::findOrFail($id);
        $eInvoiceTransaction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (E Invoice Transaction) وإعادته للعمل.
     */
    public function restore($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::withTrashed()->findOrFail($id);
        $eInvoiceTransaction->restore();
        return $eInvoiceTransaction;
    }

    /**
     * حذف نهائي للسجل من (E Invoice Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $eInvoiceTransaction = EInvoiceTransaction::withTrashed()->findOrFail($id);
        $eInvoiceTransaction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
