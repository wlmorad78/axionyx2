<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceDiscount;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceDiscountController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceDiscount::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_discount', 'store'));
        return response()->json(SalesInvoiceDiscount::create($data), 201);
    }

    public function show(SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        return $salesInvoiceDiscount;
    }

    public function update(Request $request, SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_discount', 'update', $salesInvoiceDiscount));
        $salesInvoiceDiscount->update($data);
        return response()->json($salesInvoiceDiscount);
    }

    public function destroy(SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        $salesInvoiceDiscount->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesInvoiceDiscount::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesInvoiceDiscount::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_invoice_discount', 'store');
    }
}
