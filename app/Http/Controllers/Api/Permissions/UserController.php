<?php
/**
 * =====================================================================
 * متحكم (Controller): UserController
 * الوحدة (Module): الصلاحيات والأدوار (Permissions)
 * المورد (Resource): User
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "User" ضمن وحدة "الصلاحيات والأدوار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Permissions;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * عرض قائمة سجلات (User) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = User::query()->with('userType');

        $with = $request->with ? explode(',', $request->with) : [];
        if ($with) {
            $filteredWith = array_filter($with, fn($w) => $w !== 'roles');
            if ($filteredWith) {
                $query->with($filteredWith);
            }
        }

        $companyId = $request->header('X-Company-Id')
            ?? $request->user()?->company_id;

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // فلترة بالفرع: يُرجع فقط المستخدمين المرتبطين بالفرع الحالي
        // سواء عن طريق branch_id المباشر أو عن طريق جدول user_branches
        // المدير العام (company_id = null) يرى جميع المستخدمين بدون تقييد
        $branchId = $request->input('branch_id');
        $authUser = $request->user();

        if ($branchId && $authUser && $authUser->company_id !== null) {
            $query->where(function ($q) use ($branchId) {
                // المستخدمين الذين فرعهم الرئيسي هو هذا الفرع
                $q->where('branch_id', $branchId)
                  // أو المستخدمين المرتبطين بالفرع عبر user_branches
                  ->orWhereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('usercode', 'like', "%{$search}%");
            });
        }

        if ($request->has('roles')) {
            $roles = $request->input('roles');
            if (is_string($roles)) $roles = explode(',', $roles);
            $query->whereHas('userType', function ($rq) use ($roles) {
                $rq->whereIn('name_ar', $roles);
            });
        }

        if ($request->has('trashed') && $request->trashed) {
            $query->onlyTrashed();
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->orderBy('usercode', 'asc')->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $user->roles = $user->getRolesAttribute();
            return $user;
        });

        return response()->json($users);
    }

    /**
     * إنشاء سجل جديد لـ (User) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:4',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'usercode' => 'nullable|integer',
            'user_type_id' => 'nullable|integer|exists:user_types,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'treasury_id' => 'nullable|integer|exists:treasuries,id',
        ]);

        $companyId = $request->header('X-Company-Id')
            ?? $request->user()?->company_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->input('phone'),
            'is_active' => $request->boolean('is_active', true),
            'company_id' => $companyId,
            'usercode' => $request->input('usercode'),
            'branch_id' => $request->input('branch_id'),
            'warehouse_id' => $request->input('warehouse_id'),
            'treasury_id' => $request->input('treasury_id'),
        ]);

        if ($request->has('user_type_id')) {
            $user->update(['user_type_id' => $request->user_type_id]);
        }

        $user->load('userType');

        return response()->json($user, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (User) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $user = User::with('userType')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * تحديث بيانات سجل موجود من (User) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => 'sometimes|min:4',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'usercode' => 'nullable|integer',
            'user_type_id' => 'nullable|integer|exists:user_types,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'treasury_id' => 'nullable|integer|exists:treasuries,id',
        ]);

        $data = $request->only([
            'name',
            'email',
            'phone',
            'is_active',
            'usercode',
            'branch_id',
            'warehouse_id',
            'treasury_id',
        ]);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('user_type_id')) {
            $user->update(['user_type_id' => $request->user_type_id]);
        }

        $user->load('userType');

        return response()->json($user);
    }

    /**
     * حذف سجل من (User) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (User).
     */
    public function schema()
    {
        return response()->json([
            'columns' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        ]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (User) وإعادته للعمل.
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        return response()->json($user);
    }

    /**
     * حذف نهائي للسجل من (User) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->forceDelete();
        return response()->json(['message' => 'User permanently deleted']);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (User).
     */
    public function nextCode()
    {
        $last = User::orderBy('usercode', 'desc')->first();
        $next = $last ? intval($last->usercode) + 1 : User::FIRST_USERCODE;
        return response()->json(['code' => $next]);
    }
}
