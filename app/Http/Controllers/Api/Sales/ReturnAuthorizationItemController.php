<?php
/**
 * =====================================================================
 * متحكم (Controller): ReturnAuthorizationItemController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Return Authorization Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Return Authorization Item" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReturnAuthorization;
use App\Models\Sales\ReturnAuthorizationItem;
use App\Models\HR\Employee;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ReturnAuthorizationItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Return Authorization Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request, ReturnAuthorization $returnAuthorization)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        return $returnAuthorization->items()->with($with)->get();
    }

    /**
     * تحديث بيانات سجل موجود من (Return Authorization Item) بناءً على المعرّف.
     */
    public function update(Request $request, ReturnAuthorization $returnAuthorization, ReturnAuthorizationItem $item)
    {
        if ($item->return_authorization_id !== $returnAuthorization->id) {
            return response()->json(['message' => 'Ø§Ù„ØµÙ†Ù Ù„Ø§ ÙŠÙ†ØªÙ…ÙŠ Ù„Ù‡Ø°Ø§ Ø§Ù„Ø¥Ø°Ù†'], 422);
        }

        $data = $request->validate([
            'acceptance_status' => ['sometimes', 'string', 'max:20'],
            'acceptance_notes' => ['nullable', 'string'],
            'qty' => ['sometimes', 'numeric', 'min:0'],
            'net_amount' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if (($data['acceptance_status'] ?? null) === 'accepted') {
            $data['accepted_at'] = now();
        } else {
            $data['accepted_at'] = null;
        }

        $item->update($data);
        $returnAuthorization->recalculateTotals();

        return response()->json([
            'message' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„ØµÙ†Ù Ø¨Ù†Ø¬Ø§Ø­',
            'item' => $item->fresh(),
            'return_authorization_totals' => [
                'total_sales_value' => $returnAuthorization->total_sales_value,
                'total_return_value' => $returnAuthorization->total_return_value,
                'net_debt_amount' => $returnAuthorization->net_debt_amount,
            ],
        ]);
    }

    /**
     * دالة معالجة: bulkAccept — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Return Authorization Item).
     */
    public function bulkAccept(Request $request, ReturnAuthorization $returnAuthorization)
    {
        $data = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer', 'exists:return_authorization_items,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $employee = Employee::where('email', $user->email)->first();

        ReturnAuthorizationItem::whereIn('id', $data['item_ids'])
            ->where('return_authorization_id', $returnAuthorization->id)
            ->update([
                'acceptance_status' => 'accepted',
                'accepted_at' => now(),
            ]);

        $returnAuthorization->recalculateTotals();

        return response()->json([
            'message' => 'ØªÙ… Ù‚Ø¨ÙˆÙ„ ' . count($data['item_ids']) . ' ØµÙ†Ù Ø¨Ù†Ø¬Ø§Ø­',
            'return_authorization' => $returnAuthorization->fresh()->load('items'),
        ]);
    }

    /**
     * دالة معالجة: bulkReject — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Return Authorization Item).
     */
    public function bulkReject(Request $request, ReturnAuthorization $returnAuthorization)
    {
        $data = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer', 'exists:return_authorization_items,id'],
            'notes' => ['nullable', 'string'],
        ]);

        ReturnAuthorizationItem::whereIn('id', $data['item_ids'])
            ->where('return_authorization_id', $returnAuthorization->id)
            ->update([
                'acceptance_status' => 'rejected',
            ]);

        $returnAuthorization->recalculateTotals();

        return response()->json([
            'message' => 'ØªÙ… Ø±ÙØ¶ ' . count($data['item_ids']) . ' ØµÙ†Ù',
            'return_authorization' => $returnAuthorization->fresh()->load('items'),
        ]);
    }
}