<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceTax;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceTaxController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceTax::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_tax', 'store'));
        return response()->json(SalesInvoiceTax::create($data), 201);
    }

    public function show(SalesInvoiceTax $salesInvoiceTax)
    {
        return $salesInvoiceTax;
    }

    public function update(Request $request, SalesInvoiceTax $salesInvoiceTax)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_tax', 'update', $salesInvoiceTax));
        $salesInvoiceTax->update($data);
        return response()->json($salesInvoiceTax);
    }

    public function destroy(SalesInvoiceTax $salesInvoiceTax)
    {
        $salesInvoiceTax->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesInvoiceTax::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesInvoiceTax::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_invoice_tax', 'store');
    }
}
