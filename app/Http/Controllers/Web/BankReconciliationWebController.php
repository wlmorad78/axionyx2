<?php
/**
 * =====================================================================
 * متحكم (Controller): BankReconciliationWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Bank Reconciliation Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Reconciliation Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankReconciliationWebController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Reconciliation Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BankReconciliation::with(['bankAccount'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->paginate(15);

        return view('bank-reconciliations.index', compact('reconciliations'));
    }

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Bank Reconciliation Web).
     */
    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('bank-reconciliations.create', compact('bankAccounts'));
    }

    /**
     * إنشاء سجل جديد لـ (Bank Reconciliation Web) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reconciliation_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'book_balance' => 'required|numeric',
            'system_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['system_balance'] = $data['system_balance'] ?? $data['book_balance'];
        $data['difference'] = $data['statement_balance'] - $data['system_balance'];
        $data['status'] = 'completed';

        $reconciliation = BankReconciliation::create($data);

        return redirect()
            ->route('bank-reconciliations.show', $reconciliation->id)
            ->with('success', 'تم إنشاء التسوية البنكية بنجاح');
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Reconciliation Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->load(['bankAccount']);

        return view('bank-reconciliations.show', compact('bankReconciliation'));
    }

    /**
     * حذف سجل من (Bank Reconciliation Web) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->delete();

        return redirect()
            ->route('bank-reconciliations.index')
            ->with('success', 'تم حذف التسوية البنكية بنجاح');
    }
}
