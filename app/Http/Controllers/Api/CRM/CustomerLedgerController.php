<?php
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerLedger;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerLedger::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_ledger', 'store'));
        return response()->json(CustomerLedger::create($data), 201);
    }

    public function show(CustomerLedger $customerLedger)
    {
        return $customerLedger->load(['customer', 'account', 'company', 'branch', 'createdByEmployee']);
    }

    public function update(Request $request, CustomerLedger $customerLedger)
    {
        $data = $request->validate(ValidationRules::for('customer_ledger', 'update', $customerLedger));
        $customerLedger->update($data);
        return response()->json($customerLedger);
    }

    public function destroy(CustomerLedger $customerLedger)
    {
        $customerLedger->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = CustomerLedger::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        CustomerLedger::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('customer_ledger', 'store');
    }
}
