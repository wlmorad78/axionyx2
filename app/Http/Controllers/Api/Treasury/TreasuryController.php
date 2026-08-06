<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\Treasury;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Treasury::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        } elseif ($request->user() && $request->user()->company_id) {
            $query->where('company_id', $request->user()->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $paginator = $query->paginate($request->per_page ?? 15);

        $paginator->getCollection()->transform(function ($treasury) {
            $opening = (float) $treasury->opening_balance;
            $credits = (float) $treasury->transactions()->where('type', 'credit')->sum('amount');
            $debits = (float) $treasury->transactions()->where('type', 'debit')->sum('amount');
            $treasury->balance = $opening + $credits - $debits;
            $treasury->transaction_count = $treasury->transactions()->count();
            return $treasury;
        });

        return $paginator;
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('treasury', 'store'));
        $treasury = Treasury::create($data);
        return response()->json($treasury, 201);
    }

    public function show(Treasury $treasury)
    {
        $treasury->balance = $treasury->balance;
        $treasury->transaction_count = $treasury->transactions()->count();
        return $treasury->load(['company', 'branch', 'treasuryType', 'currency']);
    }

    public function update(Request $request, Treasury $treasury)
    {
        $data = $request->validate(ValidationRules::for('treasury', 'update', $treasury));
        $treasury->update($data);
        return response()->json($treasury);
    }

    public function destroy(Treasury $treasury)
    {
        $treasury->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $treasury = Treasury::onlyTrashed()->findOrFail($id);
        $treasury->restore();
        return response()->json($treasury);
    }

    public function forceDelete(int $id)
    {
        $treasury = Treasury::onlyTrashed()->findOrFail($id);
        $treasury->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('treasury', 'store');
    }

    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = Treasury::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $lastCode = $query->orderByRaw("CAST(SUBSTR(code, 4) AS INTEGER) DESC")->value('code');
        if ($lastCode && preg_match('/^TR-(\d+)$/', $lastCode, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'TR-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }
}
