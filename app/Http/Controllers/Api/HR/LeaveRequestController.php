<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LeaveRequest::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->leave_type_id) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('leave_request', 'store'));

        return response()->json(LeaveRequest::create($data), 201);
    }

    public function show(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->load(['employee', 'leaveType', 'approver']);
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(ValidationRules::for('leave_request', 'update', $leaveRequest));

        $leaveRequest->update($data);

        return response()->json($leaveRequest);
    }

    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $leaveRequest = LeaveRequest::onlyTrashed()->findOrFail($id);
        $leaveRequest->restore();

        return response()->json($leaveRequest);
    }

    public function forceDelete(int $id)
    {
        LeaveRequest::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('leave_request', 'store');
    }
}
