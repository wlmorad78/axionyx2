<?php

namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowNotification;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkflowNotification::query()->with(['workflowInstance', 'user']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_notification', 'create'));
        $workflowNotification = WorkflowNotification::create($data);
        return response()->json($workflowNotification, 201);
    }

    public function show($id)
    {
        return WorkflowNotification::with(['workflowInstance', 'user'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $workflowNotification = WorkflowNotification::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_notification', 'update', $workflowNotification));
        $workflowNotification->update($data);
        return $workflowNotification;
    }

    public function destroy($id)
    {
        $workflowNotification = WorkflowNotification::findOrFail($id);
        $workflowNotification->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
