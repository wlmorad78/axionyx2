<?php

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ProductDiscountRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductDiscountRuleController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()?->company_id ?? $request->input('company_id');
        $query = ProductDiscountRule::with('items')
            ->where('company_id', $companyId);

        if ($request->boolean('active_only')) {
            $query->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()));
        }

        return $query->orderBy('name')->paginate($request->integer('per_page', 50));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $rule = ProductDiscountRule::create($data);
        $rule->items()->sync($data['item_ids']);

        return response()->json($rule->load('items'), 201);
    }

    public function show(Request $request, ProductDiscountRule $productDiscountRule)
    {
        $this->assertCompany($request, $productDiscountRule);
        return response()->json($productDiscountRule->load('items'));
    }

    public function update(Request $request, ProductDiscountRule $productDiscountRule)
    {
        $this->assertCompany($request, $productDiscountRule);
        $data = $this->validated($request, true);
        $itemIds = $data['item_ids'] ?? null;
        unset($data['item_ids']);
        $productDiscountRule->update($data);
        if ($itemIds !== null) {
            $productDiscountRule->items()->sync($itemIds);
        }

        return response()->json($productDiscountRule->load('items'));
    }

    public function destroy(Request $request, ProductDiscountRule $productDiscountRule)
    {
        $this->assertCompany($request, $productDiscountRule);
        $productDiscountRule->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $updating = false): array
    {
        $companyId = $request->user()?->company_id ?? $request->input('company_id');
        $rules = [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'minimum_quantity' => [$updating ? 'sometimes' : 'required', 'numeric', 'gt:0'],
            'discount_per_carton' => [$updating ? 'sometimes' : 'required', 'numeric', 'gt:0'],
            'threshold_scope' => ['sometimes', 'string', 'in:per_item,invoice_total'],
            // الأصناف مطلوبة لقواعد "لكل صنف"، أما قواعد "إجمالي الفاتورة"
            // فتخصم على كل أصناف الفاتورة فلا تحتاج نطاقاً.
            'item_ids' => [
                $updating ? 'sometimes' : 'required_unless:threshold_scope,invoice_total',
                'array',
                'min:1',
            ],
            'item_ids.*' => ['integer', 'distinct', 'exists:items,id'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
        $data = $request->validate($rules);

        if (!$companyId) {
            throw ValidationException::withMessages(['company_id' => 'الشركة مطلوبة']);
        }

        if (isset($data['item_ids'])) {
            $ownedCount = \App\Models\Item::where('company_id', $companyId)
                ->whereIn('id', $data['item_ids'])
                ->count();
            if ($ownedCount !== count($data['item_ids'])) {
                throw ValidationException::withMessages(['item_ids' => 'تأكد من أن المنتجات تتبع الشركة الحالية']);
            }
        }

        $data['company_id'] = $companyId;
        return $data;
    }

    private function assertCompany(Request $request, ProductDiscountRule $rule): void
    {
        $companyId = $request->user()?->company_id ?? $request->input('company_id');
        abort_unless((int) $companyId === (int) $rule->company_id, 404);
    }
}