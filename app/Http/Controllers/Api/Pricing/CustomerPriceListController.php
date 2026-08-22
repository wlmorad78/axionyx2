<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerPriceListController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Customer Price List
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Price List" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\CustomerPriceList;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerPriceListController extends Controller {
    public function index(Request $request) {
        $query = CustomerPriceList::with(['customer', 'priceList']);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('price_list_id')) $query->where('price_list_id', $request->price_list_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    public function store(Request $request) {
        $data = $request->validate(ValidationRules::for('customer_price_list', 'create'));
        return response()->json(CustomerPriceList::create($data), 201);
    }
    public function show($id) { return CustomerPriceList::with(['customer', 'priceList'])->findOrFail($id); }
    public function update(Request $request, $id) {
        $model = CustomerPriceList::findOrFail($id);
        $data = $request->validate(ValidationRules::for('customer_price_list', 'update', $model));
        $model->update($data);
        return $model;
    }
    public function destroy($id) { CustomerPriceList::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
