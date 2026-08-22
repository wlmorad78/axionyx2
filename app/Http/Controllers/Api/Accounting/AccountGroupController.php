<?php
/**
 * =====================================================================
 * متحكم (Controller): AccountGroupController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Account Group
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Account Group" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AccountGroupController extends Controller
{
    /**
     * عرض قائمة سجلات (Account Group) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AccountGroup::with($with);

        if ($request->account_type_id) $query->where('account_type_id', $request->account_type_id);
        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) $query->onlyTrashed();

        return $query->orderBy('sort_order')->orderBy('code')
            ->paginate($request->per_page ?? 50);
    }

    /**
     * بناء وعرض هيكل شجري (هيكلي) لسجلات (Account Group).
     */
    public function tree(Request $request)
    {
        $query = AccountGroup::with(['accountType', 'accounts' => function ($q) {
            $q->select('id', 'account_code', 'account_name');
        }]);

        if ($request->account_type_id) {
            $query->where('account_type_id', $request->account_type_id);
        }

        $groups = $query->orderBy('sort_order')->orderBy('code')->get();
        $tree = $this->buildTree($groups);

        return response()->json($tree);
    }

    /**
     * دالة معالجة: buildTree — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Account Group).
     */
    private function buildTree($groups, $parentId = null)
    {
        $tree = [];
        foreach ($groups as $group) {
            if ($group->parent_id == $parentId) {
                $children = $this->buildTree($groups, $group->id);
                $node = $group->toArray();
                $node['children'] = $children;
                $node['nature'] = $group->accountType?->nature;
                $tree[] = $node;
            }
        }
        return $tree;
    }

    /**
     * إنشاء سجل جديد لـ (Account Group) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('account_group', 'store'));

        // Compute level from parent
        if (!empty($data['parent_id'])) {
            $parent = AccountGroup::find($data['parent_id']);
            $data['level'] = $parent ? $parent->level + 1 : 1;
        } else {
            $data['level'] = $data['level'] ?? 1;
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $group = AccountGroup::create($data);

        return response()->json($group->load('accountType'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Account Group) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AccountGroup $accountGroup)
    {
        return $accountGroup->load(['accountType', 'accounts', 'parent', 'children']);
    }

    /**
     * تحديث بيانات سجل موجود من (Account Group) بناءً على المعرّف.
     */
    public function update(Request $request, AccountGroup $accountGroup)
    {
        $data = $request->validate(ValidationRules::for('account_group', 'update', $accountGroup));

        // Recompute level if parent changed
        if (isset($data['parent_id']) && $data['parent_id'] != $accountGroup->parent_id) {
            if ($data['parent_id']) {
                $parent = AccountGroup::find($data['parent_id']);
                $data['level'] = $parent ? $parent->level + 1 : 1;
            } else {
                $data['level'] = 1;
            }
        }

        $accountGroup->update($data);

        return response()->json($accountGroup->fresh()->load('accountType'));
    }

    /**
     * حذف سجل من (Account Group) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AccountGroup $accountGroup)
    {
        if ($accountGroup->accounts()->count() > 0) {
            return response()->json([
                'message' => 'لا يمكن حذف مجموعة تحتوي على حسابات'
            ], 422);
        }

        $accountGroup->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Account Group) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = AccountGroup::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Account Group) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AccountGroup::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Account Group).
     */
    public function schema()
    {
        return ValidationRules::for('account_group', 'store');
    }
}
