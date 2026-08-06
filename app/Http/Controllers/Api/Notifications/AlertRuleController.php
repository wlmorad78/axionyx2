<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notifications\AlertRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = AlertRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('alert_code', 'like', "%{$s}%")
                    ->orWhere('alert_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('severity')) $query->where('severity', $request->severity);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function nextCode()
    {
        $last = AlertRule::orderByDesc('id')->value('alert_code');
        if ($last && preg_match('/ALR-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'ALR-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert_rule', 'create'));
        return response()->json(AlertRule::create($data), 201);
    }

    public function show($id)
    {
        return AlertRule::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = AlertRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert_rule', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        AlertRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = AlertRule::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        AlertRule::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
