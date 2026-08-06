<?php
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\IssueOrder;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IssueOrderController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = IssueOrder::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->load_request_id) $query->where('load_request_id', $request->load_request_id);
        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('issue_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('issue_order', 'store'));
        return response()->json(IssueOrder::create($data), 201);
    }

    public function show(IssueOrder $issueOrder)
    {
        return $issueOrder->load([
            'company', 'branch', 'warehouse', 'loadRequest', 'employee',
            'salesTerritory', 'route', 'issuedByEmployee', 'receivedByEmployee',
            'approvedByEmployee', 'items.item', 'items.unit',
        ]);
    }

    public function update(Request $request, IssueOrder $issueOrder)
    {
        $data = $request->validate(ValidationRules::for('issue_order', 'update', $issueOrder));
        $issueOrder->update($data);
        return response()->json($issueOrder);
    }

    public function destroy(IssueOrder $issueOrder)
    {
        $issueOrder->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = IssueOrder::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        IssueOrder::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('issue_order', 'store');
    }
}
