<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingCampaignController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Campaign
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Campaign" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;

class MarketingCampaignController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Campaign) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MarketingCampaign::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('campaign_code', 'like', "%{$s}%")
                    ->orWhere('campaign_name', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Campaign) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'campaign_code' => 'required|string|max:255',
            'campaign_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'numeric|min:0',
            'status' => 'in:DRAFT,ACTIVE,COMPLETED,CANCELLED',
        ]);

        $campaign = MarketingCampaign::create($data);
        return response()->json($campaign, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Campaign) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MarketingCampaign::with('campaignCustomers.customer')->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Campaign) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $campaign = MarketingCampaign::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'campaign_code' => 'sometimes|required|string|max:255',
            'campaign_name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'budget' => 'numeric|min:0',
            'status' => 'in:DRAFT,ACTIVE,COMPLETED,CANCELLED',
        ]);

        $campaign->update($data);
        return $campaign;
    }

    /**
     * حذف سجل من (Marketing Campaign) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $campaign->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Campaign) وإعادته للعمل.
     */
    public function restore($id)
    {
        $campaign = MarketingCampaign::withTrashed()->findOrFail($id);
        $campaign->restore();
        return $campaign;
    }

    /**
     * حذف نهائي للسجل من (Marketing Campaign) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $campaign = MarketingCampaign::withTrashed()->findOrFail($id);
        $campaign->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
