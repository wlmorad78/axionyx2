<?php
/**
 * =====================================================================
 * متحكم (Controller): QuantityPriceBreakController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Quantity Price Break
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Quantity Price Break" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\QuantityPriceBreak;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class QuantityPriceBreakController extends Controller {
    public function index(Request $request) {
        $query = QuantityPriceBreak::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('pricing_rule_item_id')) $query->where('pricing_rule_item_id', $request->pricing_rule_item_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderBy('from_qty')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('quantity_price_break', 'create'));
        return response()->json(QuantityPriceBreak::create($data), 201);
    }
    public function show($id) { return QuantityPriceBreak::findOrFail($id); }
    public function update(Request $request, $id) {
        $model = QuantityPriceBreak::findOrFail($id);
        $data = $request->validate(ValidationRules::for('quantity_price_break', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { QuantityPriceBreak::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
