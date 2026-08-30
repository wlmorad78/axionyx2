<?php
/**
 * =====================================================================
 * متحكم (Controller): AccountTypeController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Account Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Account Type" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Account Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AccountType::with($with);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Account Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('account_type', 'store'));
        return response()->json(AccountType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Account Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AccountType $accountType)
    {
        return $accountType->load(['accounts']);
    }

    /**
     * تحديث بيانات سجل موجود من (Account Type) بناءً على المعرّف.
     */
    public function update(Request $request, AccountType $accountType)
    {
        $data = $request->validate(ValidationRules::for('account_type', 'update', $accountType));
        $accountType->update($data);
        return response()->json($accountType);
    }

    /**
     * حذف سجل من (Account Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AccountType $accountType)
    {
        $accountType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Account Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = AccountType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Account Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AccountType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Account Type).
     */
    public function schema()
    {
        return ValidationRules::for('account_type', 'store');
    }
}
