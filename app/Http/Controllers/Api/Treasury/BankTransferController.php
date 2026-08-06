<?php
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Treasury\BankTransfer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankTransferController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = BankTransfer::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->from_bank_account_id) $query->where('from_bank_account_id', $request->from_bank_account_id);
        if ($request->to_bank_account_id) $query->where('to_bank_account_id', $request->to_bank_account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('bank_transfer', 'store'));
        if (empty($data['transfer_no'])) {
            $data['transfer_no'] = self::generateNextCode();
        }
        if (empty($data['status'])) {
            $data['status'] = 'completed';
        }

        $transfer = DB::transaction(function () use ($data) {
            $transfer = BankTransfer::create($data);

            if ($data['status'] === 'completed') {
                $fromAccount = BankAccount::find($data['from_bank_account_id']);
                $toAccount = BankAccount::find($data['to_bank_account_id']);
                if ($fromAccount) {
                    $fromAccount->decrement('current_balance', $data['amount']);
                }
                if ($toAccount) {
                    $toAccount->increment('current_balance', $data['amount']);
                }
            }

            return $transfer;
        });

        return response()->json($transfer, 201);
    }

    public function show(BankTransfer $bankTransfer)
    {
        return $bankTransfer->load([
            'fromBankAccount', 'toBankAccount', 'company', 'branch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    public function update(Request $request, BankTransfer $bankTransfer)
    {
        $data = $request->validate(ValidationRules::for('bank_transfer', 'update', $bankTransfer));
        $bankTransfer->update($data);
        return response()->json($bankTransfer);
    }

    public function destroy(BankTransfer $bankTransfer)
    {
        DB::transaction(function () use ($bankTransfer) {
            if ($bankTransfer->status === 'completed') {
                $fromAccount = BankAccount::find($bankTransfer->from_bank_account_id);
                $toAccount = BankAccount::find($bankTransfer->to_bank_account_id);
                if ($fromAccount) {
                    $fromAccount->increment('current_balance', $bankTransfer->amount);
                }
                if ($toAccount) {
                    $toAccount->decrement('current_balance', $bankTransfer->amount);
                }
            }

            $bankTransfer->delete();
        });

        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['transfer_no' => self::generateNextCode()]);
    }

    public function restore(int $id)
    {
        $m = BankTransfer::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        BankTransfer::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('bank_transfer', 'store');
    }

    private static function generateNextCode(): string
    {
        $last = BankTransfer::orderByDesc('id')->value('transfer_no');
        if (!$last) return 'BT-00001';
        $num = (int) substr($last, 3) + 1;
        return 'BT-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
