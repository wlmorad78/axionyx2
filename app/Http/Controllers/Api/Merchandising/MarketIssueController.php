<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MarketIssue;
use Illuminate\Http\Request;

class MarketIssueController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = MarketIssue::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('issue_type', 'like', "%$s%")
                    ->orWhere('priority', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'required|exists:employees,id',
            'issue_date' => 'required|date',
            'issue_type' => 'required|in:PRICE,PROMOTION,QUALITY,AVAILABILITY,COMPETITOR_ACTIVITY,OTHER',
            'description' => 'required|string',
            'priority' => 'required|in:LOW,NORMAL,HIGH,URGENT',
            'status' => 'required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        return response()->json(MarketIssue::create($data), 201);
    }

    public function show(MarketIssue $marketIssue)
    {
        return $marketIssue->load(['customer', 'salesRep']);
    }

    public function update(Request $request, MarketIssue $marketIssue)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'sometimes|required|exists:employees,id',
            'issue_date' => 'sometimes|required|date',
            'issue_type' => 'sometimes|required|in:PRICE,PROMOTION,QUALITY,AVAILABILITY,COMPETITOR_ACTIVITY,OTHER',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:LOW,NORMAL,HIGH,URGENT',
            'status' => 'sometimes|required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        $marketIssue->update($data);
        return response()->json($marketIssue);
    }

    public function destroy(MarketIssue $marketIssue)
    {
        $marketIssue->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = MarketIssue::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        MarketIssue::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
