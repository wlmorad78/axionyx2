<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceRecord::with($with);

        if ($request->employee_id) $query->where('employee_id', $request->employee_id);
        if ($request->attendance_status_id) $query->where('attendance_status_id', $request->attendance_status_id);
        if ($request->shift_id) $query->where('shift_id', $request->shift_id);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_record', 'store'));

        return response()->json(AttendanceRecord::create($data), 201);
    }

    public function show(AttendanceRecord $attendanceRecord)
    {
        return $attendanceRecord->load(['employee', 'shift', 'attendanceStatus']);
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        $data = $request->validate(ValidationRules::for('attendance_record', 'update', $attendanceRecord));

        $attendanceRecord->update($data);

        return response()->json($attendanceRecord);
    }

    public function destroy(AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $attendanceRecord = AttendanceRecord::onlyTrashed()->findOrFail($id);
        $attendanceRecord->restore();

        return response()->json($attendanceRecord);
    }

    public function forceDelete(int $id)
    {
        AttendanceRecord::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('attendance_record', 'store');
    }
}
