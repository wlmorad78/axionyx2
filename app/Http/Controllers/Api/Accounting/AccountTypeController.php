<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AccountType::with($with);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('account_type', 'store'));
        return response()->json(AccountType::create($data), 201);
    }

    public function show(AccountType $accountType)
    {
        return $accountType->load(['accounts']);
    }

    public function update(Request $request, AccountType $accountType)
    {
        $data = $request->validate(ValidationRules::for('account_type', 'update', $accountType));
        $accountType->update($data);
        return response()->json($accountType);
    }

    public function destroy(AccountType $accountType)
    {
        $accountType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = AccountType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        AccountType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('account_type', 'store');
    }
}
