<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                    ->orWhere('device_name', 'like', "%{$s}%")
                    ->orWhere('browser', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('login_log', 'create'));
        $loginLog = LoginLog::create($data);
        return response()->json($loginLog, 201);
    }

    public function show($id)
    {
        return LoginLog::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $loginLog = LoginLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('login_log', 'update', $loginLog));
        $loginLog->update($data);
        return $loginLog;
    }

    public function destroy($id)
    {
        $loginLog = LoginLog::findOrFail($id);
        $loginLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $loginLog = LoginLog::withTrashed()->findOrFail($id);
        $loginLog->restore();
        return $loginLog;
    }

    public function forceDelete($id)
    {
        $loginLog = LoginLog::withTrashed()->findOrFail($id);
        $loginLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
