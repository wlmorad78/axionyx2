<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerOpeningBalanceController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): Customer Opening Balance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Opening Balance" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerOpeningBalance;
use Illuminate\Http\Request;

class CustomerOpeningBalanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Opening Balance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CustomerOpeningBalance::with(['customer', 'branch']);
        $companyId = $request->company_id ?? $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if ($companyId) $query->where('company_id', $companyId);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('customer', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 50);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Opening Balance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'balance_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!isset($data['company_id'])) {
            $data['company_id'] = $request->user()->company_id ?? auth()->user()->company_id;
        }
        if (!isset($data['branch_id']) && $request->branch_id) {
            $data['branch_id'] = $request->branch_id;
        }
        if (!isset($data['created_by'])) {
            $data['created_by'] = $request->user()->id ?? auth()->id();
        }
        if (!isset($data['balance'])) {
            $data['balance'] = ($data['credit'] ?? 0) - ($data['debit'] ?? 0);
        }

        $bodyBranchId = $request->json('branch_id');
        if ($bodyBranchId) {
            $data['branch_id'] = $bodyBranchId;
        }

        $record = CustomerOpeningBalance::create($data);

        $customer = $record->customer;
        if ($customer) {
            $totalCredit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('credit');
            $totalDebit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('debit');
            $customer->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json($record->load(['customer', 'branch']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Opening Balance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerOpeningBalance $customerOpeningBalance)
    {
        return $customerOpeningBalance->load(['customer', 'branch', 'createdBy']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Opening Balance) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerOpeningBalance $customerOpeningBalance)
    {
        $data = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'balance' => 'nullable|numeric',
            'balance_date' => 'sometimes|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if (isset($data['credit']) || isset($data['debit'])) {
            $debit = $data['debit'] ?? $customerOpeningBalance->debit;
            $credit = $data['credit'] ?? $customerOpeningBalance->credit;
            $data['balance'] = $credit - $debit;
        }

        $bodyBranchId = $request->json('branch_id');
        if ($bodyBranchId) {
            $data['branch_id'] = $bodyBranchId;
        }

        $customerOpeningBalance->update($data);

        $customer = $customerOpeningBalance->customer;
        if ($customer) {
            $totalCredit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('credit');
            $totalDebit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('debit');
            $customer->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json($customerOpeningBalance->load(['customer', 'branch']));
    }

    /**
     * حذف سجل من (Customer Opening Balance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerOpeningBalance $customerOpeningBalance)
    {
        $customer = $customerOpeningBalance->customer;
        $customerOpeningBalance->delete();

        if ($customer) {
            $totalCredit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('credit');
            $totalDebit = CustomerOpeningBalance::where('customer_id', $customer->id)->sum('debit');
            $customer->update(['opening_balance' => $totalCredit - $totalDebit]);
        }

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Opening Balance) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = CustomerOpeningBalance::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }
}
