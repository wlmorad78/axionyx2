<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkDayController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): WorkDay
 * ---------------------------------------------------------------------
 * الوصف:
 * نقاط النهاية (Endpoints) لإدارة أيام العمل لكل فرع/شهر/سنة.
 * ملاحظة مهمة: قراءة branch_id من الـ raw body مباشرة لأن
 * BranchScope middleware بيدمج قيمة X-Branch-Id header فوق
 * $request->input('branch_id') مما يُفقد اختيار المستخدم للفرع.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\WorkDay;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkDayController extends Controller
{
    /**
     * عرض قائمة سجلات أيام العمل مع دعم الفلترة والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkDay::query();

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
     * إنشاء سجل جديد لـ (WorkDay) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $payload = $this->payload($request);

        $data = Validator::make($payload, ValidationRules::for('work_day', 'store'))
            ->validate();

        $data = $this->withCompanyId($request, $payload, $data);

        $record = WorkDay::updateOrCreate(
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
     * عرض تفاصيل سجل محدد من (WorkDay).
     */
    public function show(WorkDay $workDay)
    {
        return $workDay;
    }

    /**
     * تحديث بيانات سجل موجود من (WorkDay) بناءً على المعرّف.
     */
    public function update(Request $request, WorkDay $workDay)
    {
        $payload = $this->payload($request);

        $data = Validator::make($payload, ValidationRules::for('work_day', 'update', $workDay))
            ->validate();

        $data = $this->withCompanyId($request, $payload, $data);

        $workDay->update($data);

        return response()->json($workDay);
    }

    /**
     * حذف سجل من (WorkDay).
     */
    public function destroy(WorkDay $workDay)
    {
        $workDay->delete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (WorkDay).
     */
    public function schema()
    {
        return ValidationRules::for('work_day', 'store');
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
