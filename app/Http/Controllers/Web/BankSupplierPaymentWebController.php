<?php
/**
 * =====================================================================
 * متحكم (Controller): BankSupplierPaymentWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Bank Supplier Payment Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Supplier Payment Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Treasury\BankSupplierPayment;
use App\Models\BankAccount;
use App\Models\Suppliers\Supplier;
use App\Models\Suppliers\SupplierLedger;
use App\Models\Purchase\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankSupplierPaymentWebController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Supplier Payment Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = BankSupplierPayment::with(['bankAccount', 'supplier'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('payment_no', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $payments = $query->paginate(15);

        $totalPaid = BankSupplierPayment::where('status', 'completed')->sum('amount');

        return view('bank-supplier-payments.index', compact('payments', 'totalPaid'));
    }

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Bank Supplier Payment Web).
     */
    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('supplier_name')->get();

        return view('bank-supplier-payments.create', compact('bankAccounts', 'suppliers'));
    }

    /**
     * إنشاء سجل جديد لـ (Bank Supplier Payment Web) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['status'] = 'completed';

        $payment = DB::transaction(function () use ($data) {
            $payment = BankSupplierPayment::create($data);
            $amount = (float) $payment->amount;

            BankAccount::where('id', $payment->bank_account_id)
                ->decrement('current_balance', $amount);

            $lastLedger = SupplierLedger::where('supplier_id', $payment->supplier_id)
                ->orderByDesc('id')->first();
            $prevBalance = $lastLedger ? (float) $lastLedger->balance : 0;

            SupplierLedger::create([
                'supplier_id' => $payment->supplier_id,
                'transaction_date' => $payment->payment_date,
                'reference_type' => BankSupplierPayment::class,
                'reference_id' => $payment->id,
                'debit' => $amount,
                'credit' => 0,
                'balance' => $prevBalance - $amount,
            ]);

            if ($payment->purchase_invoice_id) {
                $invoice = PurchaseInvoice::find($payment->purchase_invoice_id);
                if ($invoice) {
                    $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $amount;
                    $invoice->remaining_amount = max(0, $invoice->net_total - $invoice->paid_amount);
                    $invoice->save();
                }
            }

            return $payment;
        });

        return redirect()
            ->route('bank-supplier-payments.show', $payment->id)
            ->with('success', "تم إنشاء الدفعة {$payment->payment_no} بنجاح");
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Supplier Payment Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankSupplierPayment $bankSupplierPayment)
    {
        $bankSupplierPayment->load(['bankAccount', 'supplier', 'purchaseInvoice', 'company']);

        return view('bank-supplier-payments.show', compact('bankSupplierPayment'));
    }

    /**
     * حذف سجل من (Bank Supplier Payment Web) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankSupplierPayment $bankSupplierPayment)
    {
        DB::transaction(function () use ($bankSupplierPayment) {
            if ($bankSupplierPayment->status === 'completed') {
                $amount = (float) $bankSupplierPayment->amount;

                BankAccount::where('id', $bankSupplierPayment->bank_account_id)
                    ->increment('current_balance', $amount);

                SupplierLedger::where('reference_type', BankSupplierPayment::class)
                    ->where('reference_id', $bankSupplierPayment->id)
                    ->each(fn($ledger) => $ledger->forceDelete());

                if ($bankSupplierPayment->purchase_invoice_id) {
                    $invoice = PurchaseInvoice::find($bankSupplierPayment->purchase_invoice_id);
                    if ($invoice) {
                        $invoice->paid_amount = max(0, ($invoice->paid_amount ?? 0) - $amount);
                        $invoice->remaining_amount = $invoice->net_total - $invoice->paid_amount;
                        $invoice->save();
                    }
                }
            }

            $bankSupplierPayment->delete();
        });

        return redirect()
            ->route('bank-supplier-payments.index')
            ->with('success', 'تم حذف الدفعة بنجاح');
    }
}
