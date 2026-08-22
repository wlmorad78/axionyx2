<?php
/**
 * =====================================================================
 * متحكم (Controller): ContractPriceController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Contract Price
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Contract Price" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\ContractPrice;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ContractPriceController extends Controller {
    public function index(Request $request) {
        $query = ContractPrice::with(['customerAgreement', 'item', 'unit']);
        if ($request->filled('customer_agreement_id')) $query->where('customer_agreement_id', $request->customer_agreement_id);
        if ($request->filled('item_id')) $query->where('item_id', $request->item_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('contract_price', 'create'));
        return response()->json(ContractPrice::create($data), 201);
    }
    public function show($id) { return ContractPrice::with(['customerAgreement', 'item', 'unit'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = ContractPrice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('contract_price', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { ContractPrice::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
    public function restore($id) { $m = ContractPrice::withTrashed()->findOrFail($id); $m->restore(); return $m; }
    public function forceDelete($id) { ContractPrice::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
