<?php
/**
 * =====================================================================
 * متحكم (Controller): UserBranchController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): User Branch
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "User Branch" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBranchController extends Controller
{
    /**
     * Get branches for the authenticated user.
     * Returns user's assigned branches with the default branch highlighted.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $branches = DB::table('user_branches')
            ->join('branches', 'branches.id', '=', 'user_branches.branch_id')
            ->where('user_branches.user_id', $user->id)
            ->where('branches.is_active', true)
            ->select(
                'branches.id',
                'branches.code',
                'branches.name',
                'branches.name_ar',
                'branches.name_en',
                'branches.is_head_office',
                'user_branches.is_default'
            )
            ->orderBy('branches.name')
            ->get();

        $defaultBranch = $branches->firstWhere('is_default', true);

        return response()->json([
            'branches' => $branches,
            'default_branch_id' => $defaultBranch?->id,
            'branch_count' => $branches->count(),
        ]);
    }
}
