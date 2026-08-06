<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerType::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_type', 'store'));
        return response()->json(CustomerType::create($data), 201);
    }

    public function show(CustomerType $customerType)
    {
        return $customerType->load(['company']);
    }

    public function update(Request $request, CustomerType $customerType)
    {
        $data = $request->validate(ValidationRules::for('customer_type', 'update', $customerType));
        $customerType->update($data);
        return response()->json($customerType);
    }

    public function destroy(CustomerType $customerType)
    {
        if ($customerType->is_protected) {
            return response()->json(['message' => 'لا يمكن حذف هذا النوع لأنه محمي'], 403);
        }
        $customerType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = CustomerType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        $model = CustomerType::onlyTrashed()->findOrFail($id);
        if ($model->is_protected) {
            return response()->json(['message' => 'لا يمكن حذف هذا النوع لأنه محمي'], 403);
        }
        $model->forceDelete();
        return response()->json(null, 204);
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = CustomerType::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'CT-%')->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        if ($last && preg_match('/^CT-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['next_code' => 'CT-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
    }
}
