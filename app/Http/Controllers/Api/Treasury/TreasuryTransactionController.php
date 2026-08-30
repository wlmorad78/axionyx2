<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryTransactionController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Transaction" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;

class TreasuryTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryTransaction::with(['treasury']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        } elseif ($request->user() && $request->user()->company_id) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->filled('treasury_id')) $query->where('treasury_id', $request->treasury_id);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('date_from')) $query->where('transaction_date', '>=', $request->date_from . ' 00:00:00');
        if ($request->filled('date_to')) $query->where('transaction_date', '<=', $request->date_to . ' 23:59:59');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%");
            });
        }

        $transactions = $query->latest('transaction_date')->paginate($request->get('per_page', 15));

        // إضافة اسم المندوب ورقم المرجع للمعاملات المرتبطة بتسوية يومية
        $transactions->getCollection()->transform(function ($txn) {
            if ($txn->reference_type && str_contains($txn->reference_type, 'RepDailySettlement')) {
                $settlement = $txn->reference()->with('salesRep')->first();
                if ($settlement) {
                    if ($settlement->salesRep) {
                        $txn->rep_name = $settlement->salesRep->full_name_ar
                            ?? trim(($settlement->salesRep->first_name_ar ?? '') . ' ' . ($settlement->salesRep->last_name_ar ?? ''));
                    }
                    $txn->reference_no = $settlement->settlement_no;
                    // تنظيف البيان: "تسوية مندوب ناصف فايز - RDS-00001" -> "تسوية ناصف فايز"
                    if ($txn->rep_name) {
                        $txn->description = "تسوية {$txn->rep_name}";
                    }
                }
            }
            return $txn;
        });

        return $transactions;
    }
}
