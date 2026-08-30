<?php
/**
 * =====================================================================
 * متحكم (Controller): BankAccountWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Bank Account Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Account Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Account;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankAccountWebController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Account Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BankAccount::with(['company', 'currency'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('bank_name', 'like', "%$s%")
                  ->orWhere('account_number', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%");
            });
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $bankAccounts = $query->paginate(15);

        return view('bank-accounts.index', compact('bankAccounts'));
    }

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Bank Account Web).
     */
    public function create()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('name')->get();

        return view('bank-accounts.create', compact('branches', 'accounts', 'currencies'));
    }

    /**
     * إنشاء سجل جديد لـ (Bank Account Web) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_no' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20',
            'currency_id' => 'nullable|exists:currencies,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['is_active'] = $request->boolean('is_active', true);

        $bankAccount = BankAccount::create($data);

        return redirect()
            ->route('bank-accounts.show', $bankAccount->id)
            ->with('success', "تم إنشاء حساب البنك {$bankAccount->bank_name} بنجاح");
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Account Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load(['company', 'currency', 'branch', 'account']);

        return view('bank-accounts.show', compact('bankAccount'));
    }

    /**
     * عرض نموذج تعديل سجل موجود من (Bank Account Web).
     */
    public function edit(BankAccount $bankAccount)
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $accounts = Account::where('is_active', true)->orderBy('name')->get();
        $currencies = Currency::where('is_active', true)->orderBy('name')->get();

        return view('bank-accounts.edit', compact('bankAccount', 'branches', 'accounts', 'currencies'));
    }

    /**
     * تحديث بيانات سجل موجود من (Bank Account Web) بناءً على المعرّف.
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_no' => 'nullable|string|max:50',
            'iban' => 'nullable|string|max:50',
            'swift_code' => 'nullable|string|max:20',
            'currency_id' => 'nullable|exists:currencies,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $bankAccount->update($data);

        return redirect()
            ->route('bank-accounts.show', $bankAccount->id)
            ->with('success', 'تم تحديث حساب البنك بنجاح');
    }

    /**
     * حذف سجل من (Bank Account Web) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'تم حذف حساب البنك بنجاح');
    }
}
