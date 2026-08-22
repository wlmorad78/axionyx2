<?php
/**
 * =====================================================================
 * متحكم (Controller): BankTransferWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Bank Transfer Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Transfer Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankTransferWebController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Transfer Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BankTransfer::with(['fromBankAccount', 'toBankAccount'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transfers = $query->paginate(15);

        return view('bank-transfers.index', compact('transfers'));
    }

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Bank Transfer Web).
     */
    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('bank-transfers.create', compact('bankAccounts'));
    }

    /**
     * إنشاء سجل جديد لـ (Bank Transfer Web) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'from_bank_account_id' => 'required|exists:bank_accounts,id',
            'to_bank_account_id' => 'required|exists:bank_accounts,id|different:from_bank_account_id',
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['status'] = 'completed';

        $transfer = BankTransfer::create($data);

        $fromAccount = BankAccount::find($data['from_bank_account_id']);
        $toAccount = BankAccount::find($data['to_bank_account_id']);
        if ($fromAccount) {
            $fromAccount->decrement('current_balance', $data['amount']);
        }
        if ($toAccount) {
            $toAccount->increment('current_balance', $data['amount']);
        }

        return redirect()
            ->route('bank-transfers.show', $transfer->id)
            ->with('success', "تم إنشاء التحويل البنكي {$transfer->transfer_no} بنجاح");
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Transfer Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankTransfer $bankTransfer)
    {
        $bankTransfer->load(['fromBankAccount', 'toBankAccount', 'company']);

        return view('bank-transfers.show', compact('bankTransfer'));
    }

    /**
     * حذف سجل من (Bank Transfer Web) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankTransfer $bankTransfer)
    {
        if ($bankTransfer->status === 'completed') {
            $fromAccount = BankAccount::find($bankTransfer->from_bank_account_id);
            $toAccount = BankAccount::find($bankTransfer->to_bank_account_id);
            if ($fromAccount) {
                $fromAccount->increment('current_balance', $bankTransfer->amount);
            }
            if ($toAccount) {
                $toAccount->decrement('current_balance', $bankTransfer->amount);
            }
        }

        $bankTransfer->delete();

        return redirect()
            ->route('bank-transfers.index')
            ->with('success', 'تم حذف التحويل البنكي بنجاح');
    }
}
