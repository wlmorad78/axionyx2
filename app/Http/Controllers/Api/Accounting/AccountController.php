<?php
/**
 * =====================================================================
 * متحكم (Controller): AccountController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Account
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Account" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * عرض قائمة سجلات (Account) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Account::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->account_type_id) $query->where('account_type_id', $request->account_type_id);
        if ($request->account_group_id) $query->where('account_group_id', $request->account_group_id);
        if ($request->status) $query->where('status', $request->status);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('account_code', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) $query->onlyTrashed();

        return $query->orderBy('account_code')->paginate($request->per_page ?? 50);
    }

    /**
     * بناء وعرض هيكل شجري (هيكلي) لسجلات (Account).
     */
    public function tree(Request $request)
    {
        $companyId = $request->company_id ?? auth()->user()->company_id ?? null;

        $query = Account::with(['accountGroup', 'accountType'])
            ->where('company_id', $companyId);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('account_code', 'like', "%$s%")
                  ->orWhere('account_name', 'like', "%$s%");
            });
        }

        $accounts = $query->orderBy('account_code')->get();
        $tree = $this->buildTree($accounts);

        return response()->json($tree);
    }

    /**
     * دالة معالجة: buildTree — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Account).
     */
    private function buildTree($accounts, $parentId = null)
    {
        $tree = [];
        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $children = $this->buildTree($accounts, $account->id);
                $node = $account->toArray();
                $node['children'] = $children;
                $node['parent_name'] = $account->parent?->account_name;
                $node['group_name'] = $account->accountGroup?->name;
                $node['type_name'] = $account->accountType?->name;
                $node['nature'] = $account->accountType?->nature;
                $node['level'] = $account->level;
                $tree[] = $node;
            }
        }
        return $tree;
    }

    /**
     * إنشاء سجل جديد لـ (Account) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('account', 'store'));

        // Auto-generate code if empty
        if (empty($data['account_code'])) {
            $data['account_code'] = $this->generateNextCode($data['parent_id'] ?? null);
        }

        // Compute account_level from parent
        if (!empty($data['parent_id'])) {
            $parent = Account::find($data['parent_id']);
            $data['account_level'] = $parent ? $parent->account_level + 1 : 1;
        } else {
            $data['account_level'] = 1;
        }

        // Derive nature from account_type
        if (!empty($data['account_type_id'])) {
            $type = \App\Models\Accounting\AccountType::find($data['account_type_id']);
            if ($type && in_array($type->nature, ['asset', 'expense'])) {
                $data['normal_balance'] = $data['normal_balance'] ?? 'debit';
            } else {
                $data['normal_balance'] = $data['normal_balance'] ?? 'credit';
            }
        }

        // Business rule: header accounts cannot accept transactions
        if (isset($data['is_leaf']) && !$data['is_leaf']) {
            $data['allow_transactions'] = false;
        }

        // Default values
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['current_balance'] = $data['current_balance'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        $account = Account::create($data);

        return response()->json($account->load(['accountGroup', 'accountType', 'parent']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Account) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Account $account)
    {
        return $account->load([
            'accountType', 'accountGroup', 'company', 'parent',
            'children.accountGroup', 'children.accountType',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Account) بناءً على المعرّف.
     */
    public function update(Request $request, Account $account)
    {
        $data = $request->validate(ValidationRules::for('account', 'update', $account));

        // Business rule: header accounts cannot accept transactions
        if (isset($data['is_leaf']) && !$data['is_leaf']) {
            $data['allow_transactions'] = false;
        }

        // Recompute level if parent changed
        if (isset($data['parent_id']) && $data['parent_id'] != $account->parent_id) {
            if ($data['parent_id']) {
                $parent = Account::find($data['parent_id']);
                $data['account_level'] = $parent ? $parent->account_level + 1 : 1;
            } else {
                $data['account_level'] = 1;
            }
        }

        $account->update($data);

        return response()->json($account->fresh()->load(['accountGroup', 'accountType', 'parent']));
    }

    /**
     * حذف سجل من (Account) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Account $account)
    {
        if (!$account->isDeletable()) {
            return response()->json([
                'message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø­Ø³Ø§Ø¨ ÙŠØ­ØªÙˆÙŠ Ø¹Ù„Ù‰ Ø­Ø³Ø§Ø¨Ø§Øª ÙØ±Ø¹ÙŠØ©'
            ], 422);
        }

        $account->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Account).
     */
    public function nextCode(Request $request)
    {
        $parentId = $request->parent_id;
        $code = $this->generateNextCode($parentId);
        return response()->json(['account_code' => $code]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Account) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = Account::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Account) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Account::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Account).
     */
    public function schema()
    {
        return ValidationRules::for('account', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Account).
     */
    private function generateNextCode(?int $parentId = null): string
    {
        if ($parentId) {
            $parent = Account::find($parentId);
            if (!$parent) return '1000';

            $lastChild = Account::where('parent_id', $parentId)
                ->orderByDesc('account_code')
                ->value('account_code');

            if (!$lastChild) {
                return $parent->account_code . '.1';
            }

            $parts = explode('.', $lastChild);
            $lastNum = (int) end($parts);
            $parts[count($parts) - 1] = $lastNum + 1;
            return implode('.', $parts);
        }

        // Root level: find max root code
        $lastRoot = Account::whereNull('parent_id')
            ->orderByDesc('account_code')
            ->value('account_code');

        if (!$lastRoot) return '1000';

        return (string) ((int) $lastRoot + 1);
    }
}
