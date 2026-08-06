<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceAdjustment::with($with);

        if ($request->attendance_record_id) $query->where('attendance_record_id', $request->attendance_record_id);

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_adjustment', 'store'));

        return response()->json(AttendanceAdjustment::create($data), 201);
    }

    public function show(AttendanceAdjustment $attendanceAdjustment)
    {
        return $attendanceAdjustment->load(['attendanceRecord']);
    }

    public function update(Request $request, AttendanceAdjustment $attendanceAdjustment)
    {
        $data = $request->validate(ValidationRules::for('attendance_adjustment', 'update', $attendanceAdjustment));

        $attendanceAdjustment->update($data);

        return response()->json($attendanceAdjustment);
    }

    public function destroy(AttendanceAdjustment $attendanceAdjustment)
    {
        $attendanceAdjustment->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $attendanceAdjustment = AttendanceAdjustment::onlyTrashed()->findOrFail($id);
        $attendanceAdjustment->restore();

        return response()->json($attendanceAdjustment);
    }

    public function forceDelete(int $id)
    {
        AttendanceAdjustment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('attendance_adjustment', 'store');
    }
}
