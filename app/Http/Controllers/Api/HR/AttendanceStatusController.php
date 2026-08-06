<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceStatus;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceStatusController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceStatus::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_status', 'store'));

        return response()->json(AttendanceStatus::create($data), 201);
    }

    public function show(AttendanceStatus $attendanceStatus)
    {
        return $attendanceStatus;
    }

    public function update(Request $request, AttendanceStatus $attendanceStatus)
    {
        $data = $request->validate(ValidationRules::for('attendance_status', 'update', $attendanceStatus));

        $attendanceStatus->update($data);

        return response()->json($attendanceStatus);
    }

    public function destroy(AttendanceStatus $attendanceStatus)
    {
        if ($attendanceStatus->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $attendanceStatus->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $attendanceStatus = AttendanceStatus::onlyTrashed()->findOrFail($id);
        $attendanceStatus->restore();

        return response()->json($attendanceStatus);
    }

    public function forceDelete(int $id)
    {
        AttendanceStatus::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('attendance_status', 'store');
    }
}
