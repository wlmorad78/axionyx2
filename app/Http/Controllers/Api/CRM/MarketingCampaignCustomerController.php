<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingCampaignCustomerController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Campaign Customer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Campaign Customer" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaignCustomer;
use Illuminate\Http\Request;

class MarketingCampaignCustomerController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Campaign Customer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MarketingCampaignCustomer::with(['campaign', 'customer']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('target_amount', 'like', "%{$s}%");
            });
        }

        if ($request->filled('marketing_campaign_id')) $query->where('marketing_campaign_id', $request->marketing_campaign_id);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Campaign Customer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_campaign_id' => 'required|exists:marketing_campaigns,id',
            'customer_id' => 'required|exists:customers,id',
            'target_amount' => 'numeric|min:0',
            'actual_amount' => 'numeric|min:0',
        ]);

        $record = MarketingCampaignCustomer::create($data);
        return response()->json($record, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Campaign Customer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MarketingCampaignCustomer::with(['campaign', 'customer'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Campaign Customer) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $record = MarketingCampaignCustomer::findOrFail($id);

        $data = $request->validate([
            'marketing_campaign_id' => 'sometimes|required|exists:marketing_campaigns,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'target_amount' => 'numeric|min:0',
            'actual_amount' => 'numeric|min:0',
        ]);

        $record->update($data);
        return $record;
    }

    /**
     * حذف سجل من (Marketing Campaign Customer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $record = MarketingCampaignCustomer::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Campaign Customer) وإعادته للعمل.
     */
    public function restore($id)
    {
        $record = MarketingCampaignCustomer::withTrashed()->findOrFail($id);
        $record->restore();
        return $record;
    }

    /**
     * حذف نهائي للسجل من (Marketing Campaign Customer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $record = MarketingCampaignCustomer::withTrashed()->findOrFail($id);
        $record->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
