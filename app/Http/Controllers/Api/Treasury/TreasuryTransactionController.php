<?php

namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryTransaction;
use Illuminate\Http\Request;

class TreasuryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryTransaction::with(['treasury']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        } elseif ($request->user() && $request->user()->company_id) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->filled('treasury_id')) $query->where('treasury_id', $request->treasury_id);
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('date_from')) $query->where('transaction_date', '>=', $request->date_from . ' 00:00:00');
        if ($request->filled('date_to')) $query->where('transaction_date', '<=', $request->date_to . ' 23:59:59');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%");
            });
        }

        return $query->latest('transaction_date')->paginate($request->get('per_page', 15));
    }
}
