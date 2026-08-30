<?php
/**
 * =====================================================================
 * متحكم (Controller): CompanyController
 * الوحدة (Module): بيانات الشركة (Company)
 * المورد (Resource): Company
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Company" ضمن وحدة "بيانات الشركة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * عرض قائمة سجلات (Company) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Company::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        $user = $request->user();
        if ($user && $user->company_id) {
            $query->where('id', $user->company_id);
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Company) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company', 'store'));
        if (!isset($data['code']) || empty($data['code'])) {
            $data['code'] = $this->generateNextCode();
        }
        $company = Company::create($data);

        return response()->json($company, 201);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Company).
     */
    public function nextCode()
    {
        return response()->json(['next_code' => $this->generateNextCode()]);
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Company).
     */
    private function generateNextCode(): string
    {
        $prefix = 'CMP-';
        $codes = Company::withTrashed()
            ->where('code', 'like', "$prefix%")
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            $num = (int) substr($code, strlen($prefix));
            if ($num > $max) $max = $num;
        }

        return $prefix . str_pad($max + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * عرض تفاصيل سجل محدد من (Company) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Company $company)
    {
        return $company->load(['currency', 'country', 'governorate', 'city', 'area', 'street']);
    }

    /**
     * تحديث بيانات سجل موجود من (Company) بناءً على المعرّف.
     */
    public function update(Request $request, Company $company)
    {
        $data = $request->validate(ValidationRules::for('company', 'update', $company));
        $company->update($data);

        return response()->json($company);
    }

    /**
     * حذف سجل من (Company) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Company) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->restore();

        return response()->json($company);
    }

    /**
     * حذف نهائي للسجل من (Company) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Company).
     */
    public function schema()
    {
        return ValidationRules::for('company', 'store');
    }
}
