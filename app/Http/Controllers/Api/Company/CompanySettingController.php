<?php
/**
 * =====================================================================
 * متحكم (Controller): CompanySettingController
 * الوحدة (Module): بيانات الشركة (Company)
 * المورد (Resource): Company Setting
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Company Setting" ضمن وحدة "بيانات الشركة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    /**
     * GET /api/company-settings
     * Get all settings for current company.
     */
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;
        $settings = CompanySetting::getAll($companyId);
        return response()->json(['data' => $settings]);
    }

    /**
     * GET /api/company-settings/{group}
     * Get settings by group.
     */
    public function byGroup(Request $request, string $group)
    {
        $companyId = $request->user()->company_id;
        $settings = CompanySetting::where('company_id', $companyId)
            ->where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
        return response()->json(['data' => $settings]);
    }

    /**
     * PUT /api/company-settings
     * Update multiple settings at once.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.group' => 'required|string',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'nullable|string|in:string,integer,decimal,boolean,json',
        ]);

        $companyId = $request->user()->company_id;

        foreach ($validated['settings'] as $s) {
            CompanySetting::set(
                $companyId,
                $s['group'],
                $s['key'],
                $s['value'],
                $s['type'] ?? 'string'
            );
        }

        return response()->json(['message' => 'Settings updated']);
    }

    /**
     * DELETE /api/company-settings/{group}/{key}
     */
    public function destroy(Request $request, string $group, string $key)
    {
        CompanySetting::where('company_id', $request->user()->company_id)
            ->where('group', $group)
            ->where('key', $key)
            ->delete();

        return response()->json(['message' => 'Setting deleted']);
    }
}
