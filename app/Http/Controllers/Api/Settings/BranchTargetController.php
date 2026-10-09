<?php
/**
 * =====================================================================
 * متحكم (Controller): BranchTargetController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): BranchTarget
 * ---------------------------------------------------------------------
 * الوصف:
 * نقاط النهاية (Endpoints) لإدارة أهداف الفروع لكل فرع/شهر/سنة.
 * ملاحظة مهمة: قراءة branch_id من الـ raw body مباشرة لأن
 * BranchScope middleware بيدمج قيمة X-Branch-Id header فوق
 * $request->input('branch_id') مما يُفقد اختيار المستخدم للفرع.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\BranchTarget;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchTargetController extends Controller
{
    /**
     * عرض قائمة سجلات أهداف الفروع مع دعم الفلترة والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BranchTarget::query();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }
        // branch_id يُقرأ من raw query string فقط (يمنع تداخل BranchScope header)
        parse_str((string) $request->server('QUERY_STRING', ''), $queryString);
        if (isset($queryString['branch_id']) && $queryString['branch_id'] !== '') {
            $query->where('branch_id', $queryString['branch_id']);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->input('year'));
        }
        if ($request->filled('month')) {
            $query->where('month', $request->input('month'));
        }

        return $query->orderBy('branch_id')->orderBy('year')->orderBy('month')
            ->paginate($request->input('per_page') ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (BranchTarget) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $payload = $this->payload($request);

        $data = Validator::make($payload, ValidationRules::for('branch_target', 'store'))
            ->validate();

        $data = $this->withCompanyId($request, $payload, $data);

        $record = BranchTarget::updateOrCreate(
            [
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'],
                'year' => $data['year'],
                'month' => $data['month'],
            ],
            $data
        );

        return response()->json($record, $record->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * عرض تفاصيل سجل محدد من (BranchTarget).
     */
    public function show(BranchTarget $branchTarget)
    {
        return $branchTarget;
    }

    /**
     * تحديث بيانات سجل موجود من (BranchTarget) بناءً على المعرّف.
     */
    public function update(Request $request, BranchTarget $branchTarget)
    {
        $payload = $this->payload($request);

        $data = Validator::make($payload, ValidationRules::for('branch_target', 'update', $branchTarget))
            ->validate();

        $data = $this->withCompanyId($request, $payload, $data);

        $branchTarget->update($data);

        return response()->json($branchTarget);
    }

    /**
     * حذف سجل من (BranchTarget).
     */
    public function destroy(BranchTarget $branchTarget)
    {
        $branchTarget->delete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (BranchTarget).
     */
    public function schema()
    {
        return ValidationRules::for('branch_target', 'store');
    }

    /**
     * قراءة الـ body الخام (لتفادي تداخل BranchScope مع header).
     */
    private function payload(Request $request): array
    {
        $body = json_decode($request->getContent(), true);

        return is_array($body) ? $body : [];
    }

    /**
     * ضبط company_id من الـ body أو من سياق الشركة.
     */
    private function withCompanyId(Request $request, array $payload, array $data): array
    {
        $data['company_id'] = $payload['company_id']
            ?? $request->header('X-Company-Id')
            ?? $request->user()?->company_id
            ?? \App\Services\CompanyContext::id();

        return $data;
    }
}
