<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingAssetMaintenanceController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Asset Maintenance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Asset Maintenance" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAssetMaintenance;
use Illuminate\Http\Request;

class MarketingAssetMaintenanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Asset Maintenance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MarketingAssetMaintenance::with('marketingAsset');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('maintenance_type', 'like', "%{$s}%")
                    ->orWhere('vendor_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('marketing_asset_id')) $query->where('marketing_asset_id', $request->marketing_asset_id);
        if ($request->filled('maintenance_type')) $query->where('maintenance_type', $request->maintenance_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Asset Maintenance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_asset_id' => 'required|exists:marketing_assets,id',
            'maintenance_date' => 'required|date',
            'maintenance_type' => 'required|string|max:255',
            'cost' => 'numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $maintenance = MarketingAssetMaintenance::create($data);
        return response()->json($maintenance, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Asset Maintenance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MarketingAssetMaintenance::with('marketingAsset')->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Asset Maintenance) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $maintenance = MarketingAssetMaintenance::findOrFail($id);

        $data = $request->validate([
            'marketing_asset_id' => 'sometimes|required|exists:marketing_assets,id',
            'maintenance_date' => 'sometimes|required|date',
            'maintenance_type' => 'sometimes|required|string|max:255',
            'cost' => 'numeric|min:0',
            'vendor_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $maintenance->update($data);
        return $maintenance;
    }

    /**
     * حذف سجل من (Marketing Asset Maintenance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $maintenance = MarketingAssetMaintenance::findOrFail($id);
        $maintenance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Asset Maintenance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $maintenance = MarketingAssetMaintenance::withTrashed()->findOrFail($id);
        $maintenance->restore();
        return $maintenance;
    }

    /**
     * حذف نهائي للسجل من (Marketing Asset Maintenance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $maintenance = MarketingAssetMaintenance::withTrashed()->findOrFail($id);
        $maintenance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
