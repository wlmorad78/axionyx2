<?php
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PurchaseRequest::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->requested_by) $query->where('requested_by', $request->requested_by);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('purchase_request', 'store'));
        return response()->json(PurchaseRequest::create($data), 201);
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        return $purchaseRequest->load([
            'company', 'branch', 'requestedByEmployee', 'createdByEmployee', 'approvedByEmployee',
            'items.item', 'items.unit',
        ]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $data = $request->validate(ValidationRules::for('purchase_request', 'update', $purchaseRequest));
        $purchaseRequest->update($data);
        return response()->json($purchaseRequest);
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = PurchaseRequest::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        PurchaseRequest::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('purchase_request', 'store');
    }
}
