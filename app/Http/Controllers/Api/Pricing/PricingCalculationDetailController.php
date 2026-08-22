<?php
/**
 * =====================================================================
 * متحكم (Controller): PricingCalculationDetailController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Pricing Calculation Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Pricing Calculation Detail" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PricingCalculationDetail;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PricingCalculationDetailController extends Controller {
    public function index(Request $request) {
        $query = PricingCalculationDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('pricing_calculation_id')) $query->where('pricing_calculation_id', $request->pricing_calculation_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderBy('calculation_step')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('pricing_calculation_detail', 'create'));
        return response()->json(PricingCalculationDetail::create($data), 201);
    }
    public function show($id) { return PricingCalculationDetail::findOrFail($id); }
    public function update(Request $request, $id) {
        $model = PricingCalculationDetail::findOrFail($id);
        $data = $request->validate(ValidationRules::for('pricing_calculation_detail', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { PricingCalculationDetail::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
