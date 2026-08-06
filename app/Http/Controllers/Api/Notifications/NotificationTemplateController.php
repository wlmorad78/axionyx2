<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notifications\NotificationTemplate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::with(['notificationType', 'channel']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_code', 'like', "%{$s}%")
                    ->orWhere('template_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('notification_type_id')) $query->where('notification_type_id', $request->notification_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_template', 'create'));
        $notificationTemplate = NotificationTemplate::create($data);
        return response()->json($notificationTemplate, 201);
    }

    public function show($id)
    {
        return NotificationTemplate::with(['notificationType', 'channel'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $notificationTemplate = NotificationTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_template', 'update', $notificationTemplate));
        $notificationTemplate->update($data);
        return $notificationTemplate;
    }

    public function destroy($id)
    {
        NotificationTemplate::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $notificationTemplate = NotificationTemplate::withTrashed()->findOrFail($id);
        $notificationTemplate->restore();
        return $notificationTemplate;
    }

    public function forceDelete($id)
    {
        NotificationTemplate::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
