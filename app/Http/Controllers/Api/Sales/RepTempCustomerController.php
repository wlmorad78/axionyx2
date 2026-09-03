<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RepTempCustomer;
use Illuminate\Http\Request;

class RepTempCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = RepTempCustomer::with(['employee', 'customer']);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('customer', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                  ->orWhere('code', 'like', "%$s%");
            });
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'customer_id' => 'required|exists:customers,id',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        $exists = RepTempCustomer::where('employee_id', $request->employee_id)
            ->where('customer_id', $request->customer_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'هذا العميل مربوط بالفعل بالمندوب'], 422);
        }

        $record = RepTempCustomer::create([
            'company_id' => $companyId,
            'employee_id' => $request->employee_id,
            'customer_id' => $request->customer_id,
            'notes' => $request->notes,
        ]);

        return response()->json($record->load(['employee', 'customer']), 201);
    }

    public function show(RepTempCustomer $repTempCustomer)
    {
        return $repTempCustomer->load(['employee', 'customer']);
    }

    public function destroy(RepTempCustomer $repTempCustomer)
    {
        $repTempCustomer->delete();
        return response()->json(null, 204);
    }

    public function bulkAssign(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;
        $employeeId = $request->employee_id;
        $assigned = 0;
        $skipped = 0;

        foreach ($request->customer_ids as $customerId) {
            $exists = RepTempCustomer::where('employee_id', $employeeId)
                ->where('customer_id', $customerId)
                ->exists();

            if (!$exists) {
                RepTempCustomer::create([
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'customer_id' => $customerId,
                ]);
                $assigned++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'message' => 'تم الربط بنجاح',
            'assigned' => $assigned,
            'skipped' => $skipped,
        ]);
    }

    public function bulkDetach(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:rep_temp_customers,id',
        ]);

        RepTempCustomer::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'تم فك الربط بنجاح']);
    }
}
