<?php
/**
 * =====================================================================
 * متحكم (Controller): CatalogController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): Catalog
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Catalog" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPrice;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * شاشة جديدة للقراءة فقط: وحدات كل صنف + قوائم أسعاره.
     * لا يوجد أي تعديل على البيانات أو على الشغل الحالي.
     */
    public function itemsWithPricing(Request $request)
    {
        $companyId = $request->user()->company_id;
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 20), 100);

        $items = Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%$search%")
                  ->orWhere('name_en', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            }))
            ->with([
                'baseUnit:id,name_ar,name_en',
                'itemCategory:id,name_ar,name_en',
                'itemUnits.unit:id,name_ar,name_en',
            ])
            ->orderBy('code')
            ->paginate($perPage);

        // تحميل الأسعار من الموديل الصحيح (تجاوز علاقة prices المعطوبة في Item)
        $ids = $items->pluck('id')->all();
        $pricesByItem = ItemPrice::whereIn('item_id', $ids)
            ->with([
                'priceList:id,name_ar,name_en,is_default,pricing_method_id',
                'priceList.pricingMethod:id,name_ar,name_en',
                'unit:id,name_ar,name_en',
            ])
            ->get()
            ->groupBy('item_id');

        $items->getCollection()->transform(function (Item $item) use ($pricesByItem) {
            $units = $item->itemUnits->map(function ($iu) {
                return [
                    'unit_id' => $iu->unit_id,
                    'unit' => $iu->unit?->name_ar ?? $iu->unit?->name_en ?? '',
                    'conversion_factor' => (float) $iu->conversion_factor,
                    'is_default' => (bool) $iu->is_default,
                    'is_purchase_unit' => (bool) $iu->is_purchase_unit,
                    'is_sales_unit' => (bool) $iu->is_sales_unit,
                    'purchase_price' => (float) $iu->purchase_price,
                    'sale_price' => (float) $iu->sale_price,
                    'consumer_price' => (float) $iu->consumer_price,
                ];
            });

            // تجميع الأسعار حسب قائمة السعر
            $priceLists = [];
            foreach (($pricesByItem[$item->id] ?? []) as $p) {
                $plId = $p->price_list_id;
                if (!isset($priceLists[$plId])) {
                    $priceLists[$plId] = [
                        'price_list_id' => $plId,
                        'price_list_name' => $p->priceList?->name_ar ?? $p->priceList?->name_en ?? '',
                        'is_default' => (bool) ($p->priceList?->is_default ?? false),
                        'pricing_method' => $p->priceList?->pricingMethod?->name_ar
                            ?? $p->priceList?->pricingMethod?->name_en ?? '',
                        'unit_prices' => [],
                    ];
                }
                $priceLists[$plId]['unit_prices'][] = [
                    'unit' => $p->unit?->name_ar ?? $p->unit?->name_en ?? '',
                    'price' => (float) $p->price,
                    'effective_from' => $p->effective_from?->format('Y-m-d'),
                    'effective_to' => $p->effective_to?->format('Y-m-d'),
                    'is_active' => (bool) $p->is_active,
                ];
            }

            return [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name_ar ?? $item->name_en ?? '',
                'name_ar' => $item->name_ar,
                'category' => $item->itemCategory?->name_ar ?? $item->itemCategory?->name_en ?? '',
                'base_unit' => $item->baseUnit?->name_ar ?? $item->baseUnit?->name_en ?? '',
                'is_taxable' => (bool) $item->is_taxable,
                'units' => $units,
                'price_lists' => array_values($priceLists),
            ];
        });

        return response()->json($items);
    }
}
