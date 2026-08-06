<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MessageLog};
use App\Support\ValidationRules;

class MessageLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('channel', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('message_log', 'create'));
        $messageLog = MessageLog::create($data);
        return response()->json($messageLog, 201);
    }

    public function show($id)
    {
        return MessageLog::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $messageLog = MessageLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('message_log', 'update', $messageLog));
        $messageLog->update($data);
        return $messageLog;
    }

    public function destroy($id)
    {
        $messageLog = MessageLog::findOrFail($id);
        $messageLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $messageLog = MessageLog::withTrashed()->findOrFail($id);
        $messageLog->restore();
        return $messageLog;
    }

    public function forceDelete($id)
    {
        $messageLog = MessageLog::withTrashed()->findOrFail($id);
        $messageLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
