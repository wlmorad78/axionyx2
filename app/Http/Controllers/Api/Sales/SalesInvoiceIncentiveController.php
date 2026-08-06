<?php
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceIncentive;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceIncentiveController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceIncentive::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_incentive', 'store'));
        return response()->json(SalesInvoiceIncentive::create($data), 201);
    }

    public function show(SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        return $salesInvoiceIncentive->load(['salesIncentive']);
    }

    public function update(Request $request, SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_incentive', 'update', $salesInvoiceIncentive));
        $salesInvoiceIncentive->update($data);
        return response()->json($salesInvoiceIncentive);
    }

    public function destroy(SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        $salesInvoiceIncentive->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = SalesInvoiceIncentive::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        SalesInvoiceIncentive::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('sales_invoice_incentive', 'store');
    }
}
