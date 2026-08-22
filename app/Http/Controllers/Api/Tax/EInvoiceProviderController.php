<?php
/**
 * =====================================================================
 * متحكم (Controller): EInvoiceProviderController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): E Invoice Provider
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "E Invoice Provider" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{EInvoiceProvider};
use App\Support\ValidationRules;

class EInvoiceProviderController extends Controller
{
    /**
     * عرض قائمة سجلات (E Invoice Provider) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = EInvoiceProvider::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('provider_name', 'like', "%{$s}%")
                  ->orWhere('provider_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (E Invoice Provider) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('e_invoice_provider', 'create'));
        $eInvoiceProvider = EInvoiceProvider::create($data);
        return response()->json($eInvoiceProvider, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (E Invoice Provider) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return EInvoiceProvider::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (E Invoice Provider) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $eInvoiceProvider = EInvoiceProvider::findOrFail($id);
        $data = $request->validate(ValidationRules::for('e_invoice_provider', 'update', $eInvoiceProvider));
        $eInvoiceProvider->update($data);
        return $eInvoiceProvider;
    }

    /**
     * حذف سجل من (E Invoice Provider) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $eInvoiceProvider = EInvoiceProvider::findOrFail($id);
        $eInvoiceProvider->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (E Invoice Provider) وإعادته للعمل.
     */
    public function restore($id)
    {
        $eInvoiceProvider = EInvoiceProvider::withTrashed()->findOrFail($id);
        $eInvoiceProvider->restore();
        return $eInvoiceProvider;
    }

    /**
     * حذف نهائي للسجل من (E Invoice Provider) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $eInvoiceProvider = EInvoiceProvider::withTrashed()->findOrFail($id);
        $eInvoiceProvider->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
