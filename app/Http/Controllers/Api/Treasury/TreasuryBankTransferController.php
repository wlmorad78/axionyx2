<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryBankTransferController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Bank Transfer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Bank Transfer" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryBankTransfer;
use App\Models\Treasury\TreasuryTransaction;
use App\Models\BankAccount;
use App\Models\Treasury\Treasury;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreasuryBankTransferController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Bank Transfer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['treasury', 'bankAccount'];
        $query = TreasuryBankTransfer::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->transfer_type) $query->where('transfer_type', $request->transfer_type);
        if ($request->treasury_id) $query->where('treasury_id', $request->treasury_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")
                    ->orWhere('description', 'like', "%$s%")
                    ->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Bank Transfer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('treasury_bank_transfer', 'store'));

        if (empty($data['transfer_no'])) {
            $data['transfer_no'] = self::generateNextCode();
        }

        $transfer = DB::transaction(function () use ($data) {
            $transfer = TreasuryBankTransfer::create($data);

            if (($data['status'] ?? 'draft') === 'completed') {
                self::executeTransfer($transfer);
            }

            return $transfer;
        });

        return response()->json($transfer->load(['treasury', 'bankAccount']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Bank Transfer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TreasuryBankTransfer $treasuryBankTransfer)
    {
        return $treasuryBankTransfer->load([
            'treasury', 'bankAccount', 'company', 'branch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Bank Transfer) بناءً على المعرّف.
     */
    public function update(Request $request, TreasuryBankTransfer $treasuryBankTransfer)
    {
        $data = $request->validate(ValidationRules::for('treasury_bank_transfer', 'update', $treasuryBankTransfer));
        $treasuryBankTransfer->update($data);
        return response()->json($treasuryBankTransfer);
    }

    /**
     * حذف سجل من (Treasury Bank Transfer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TreasuryBankTransfer $treasuryBankTransfer)
    {
        DB::transaction(function () use ($treasuryBankTransfer) {
            if ($treasuryBankTransfer->status === 'completed') {
                self::reverseTransfer($treasuryBankTransfer);
            }
            $treasuryBankTransfer->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Treasury Bank Transfer).
     */
    public function approve(TreasuryBankTransfer $treasuryBankTransfer)
    {
        if ($treasuryBankTransfer->status !== 'draft') {
            return response()->json(['message' => 'Only draft transfers can be approved'], 422);
        }

        DB::transaction(function () use ($treasuryBankTransfer) {
            $treasuryBankTransfer->update([
                'status' => 'completed',
                'approved_by' => auth()->user()?->employee?->id,
                'approved_at' => now(),
            ]);
            self::executeTransfer($treasuryBankTransfer);
        });

        return response()->json($treasuryBankTransfer->load(['treasury', 'bankAccount']));
    }

    /**
     * تنفيذ إجراء (عملية حالة) على سجل من (Treasury Bank Transfer).
     */
    public function cancel(TreasuryBankTransfer $treasuryBankTransfer)
    {
        if ($treasuryBankTransfer->status === 'cancelled') {
            return response()->json(['message' => 'Transfer already cancelled'], 422);
        }

        DB::transaction(function () use ($treasuryBankTransfer) {
            if ($treasuryBankTransfer->status === 'completed') {
                self::reverseTransfer($treasuryBankTransfer);
            }
            $treasuryBankTransfer->update(['status' => 'cancelled']);
        });

        return response()->json($treasuryBankTransfer->load(['treasury', 'bankAccount']));
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Treasury Bank Transfer).
     */
    public function nextCode()
    {
        return response()->json(['transfer_no' => self::generateNextCode()]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Bank Transfer) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = TreasuryBankTransfer::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            $m->restore();
            if ($m->status === 'completed') {
                self::executeTransfer($m);
            }
        });

        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Treasury Bank Transfer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $m = TreasuryBankTransfer::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($m) {
            if ($m->status === 'completed') {
                self::reverseTransfer($m);
            }
            $m->forceDelete();
        });

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Treasury Bank Transfer).
     */
    public function schema()
    {
        return ValidationRules::for('treasury_bank_transfer', 'store');
    }

    /**
     * دالة معالجة: executeTransfer — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Treasury Bank Transfer).
     */
    private static function executeTransfer(TreasuryBankTransfer $transfer): void
    {
        $amount = (float) $transfer->amount;
        if ($amount <= 0) return;

        if ($transfer->transfer_type === 'treasury_to_bank') {
            TreasuryTransaction::create([
                'company_id' => $transfer->company_id,
                'treasury_id' => $transfer->treasury_id,
                'type' => 'debit',
                'amount' => $amount,
                'reference_type' => TreasuryBankTransfer::class,
                'reference_id' => $transfer->id,
                'description' => "تحويل من الخزنة إلى البنك - {$transfer->transfer_no}",
                'transaction_date' => $transfer->transfer_date,
                'created_by' => $transfer->created_by,
            ]);

            BankAccount::where('id', $transfer->bank_account_id)
                ->increment('current_balance', $amount);
        } else {
            TreasuryTransaction::create([
                'company_id' => $transfer->company_id,
                'treasury_id' => $transfer->treasury_id,
                'type' => 'credit',
                'amount' => $amount,
                'reference_type' => TreasuryBankTransfer::class,
                'reference_id' => $transfer->id,
                'description' => "تحويل من البنك إلى الخزنة - {$transfer->transfer_no}",
                'transaction_date' => $transfer->transfer_date,
                'created_by' => $transfer->created_by,
            ]);

            BankAccount::where('id', $transfer->bank_account_id)
                ->decrement('current_balance', $amount);
        }
    }

    /**
     * دالة معالجة: reverseTransfer — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Treasury Bank Transfer).
     */
    private static function reverseTransfer(TreasuryBankTransfer $transfer): void
    {
        TreasuryTransaction::where('reference_type', TreasuryBankTransfer::class)
            ->where('reference_id', $transfer->id)
            ->each(fn($txn) => $txn->forceDelete());

        $amount = (float) $transfer->amount;
        if ($amount <= 0) return;

        if ($transfer->transfer_type === 'treasury_to_bank') {
            BankAccount::where('id', $transfer->bank_account_id)
                ->decrement('current_balance', $amount);
        } else {
            BankAccount::where('id', $transfer->bank_account_id)
                ->increment('current_balance', $amount);
        }
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Treasury Bank Transfer).
     */
    private static function generateNextCode(): string
    {
        $last = TreasuryBankTransfer::withTrashed()
            ->orderByRaw("CAST(SUBSTR(transfer_no, 4) AS INTEGER) DESC")
            ->first();
        if (!$last) return 'TB-00001';
        $num = 1;
        if (preg_match('/^TB-(\d+)$/', $last->transfer_no, $m)) {
            $num = intval($m[1]) + 1;
        }
        return 'TB-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
