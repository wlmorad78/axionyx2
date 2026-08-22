<?php
/**
 * =====================================================================
 * متحكم (Controller): PricingExceptionController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Pricing Exception
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Pricing Exception" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingException;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingExceptionController extends Controller {
    public function index(Request $request) {
        $query = PricingException::with(['pricingRule', 'customer', 'item']);
        if ($request->filled('pricing_rule_id')) $query->where('pricing_rule_id', $request->pricing_rule_id);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_exception', 'create'));
        return response()->json(PricingException::create($data), 201);
    }
    public function show($id) { return PricingException::with(['pricingRule', 'customer', 'item'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingException::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_exception', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingException::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
