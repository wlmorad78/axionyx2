<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryBankTransfer;
use App\Models\Treasury\Treasury;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreasuryBankTransferWebController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryBankTransfer::with(['treasury', 'bankAccount'])
            ->orderByDesc('id');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->transfer_type) {
            $query->where('transfer_type', $request->transfer_type);
        }

        $transfers = $query->paginate(15);

        $totalTreasuryToBank = TreasuryBankTransfer::where('transfer_type', 'treasury_to_bank')
            ->where('status', 'completed')->sum('amount');
        $totalBankToTreasury = TreasuryBankTransfer::where('transfer_type', 'bank_to_treasury')
            ->where('status', 'completed')->sum('amount');

        return view('treasury-bank-transfers.index', compact(
            'transfers', 'totalTreasuryToBank', 'totalBankToTreasury'
        ));
    }

    public function create()
    {
        $treasuries = Treasury::where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        return view('treasury-bank-transfers.create', compact('treasuries', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transfer_type' => 'required|in:treasury_to_bank,bank_to_treasury',
            'treasury_id' => 'required|exists:treasuries,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = Auth::user()->company_id;
        $data['status'] = 'completed';

        $transfer = DB::transaction(function () use ($data) {
            $transfer = TreasuryBankTransfer::create($data);

            $amount = (float) $transfer->amount;

            if ($transfer->transfer_type === 'treasury_to_bank') {
                \App\Models\Treasury\TreasuryTransaction::create([
                    'company_id' => $transfer->company_id,
                    'treasury_id' => $transfer->treasury_id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'reference_type' => TreasuryBankTransfer::class,
                    'reference_id' => $transfer->id,
                    'description' => "تحويل من الخزنة إلى البنك - {$transfer->transfer_no}",
                    'transaction_date' => $transfer->transfer_date,
                    'created_by' => auth()->user()?->employee?->id,
                ]);

                BankAccount::where('id', $transfer->bank_account_id)
                    ->increment('current_balance', $amount);
            } else {
                \App\Models\Treasury\TreasuryTransaction::create([
                    'company_id' => $transfer->company_id,
                    'treasury_id' => $transfer->treasury_id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'reference_type' => TreasuryBankTransfer::class,
                    'reference_id' => $transfer->id,
                    'description' => "تحويل من البنك إلى الخزنة - {$transfer->transfer_no}",
                    'transaction_date' => $transfer->transfer_date,
                    'created_by' => auth()->user()?->employee?->id,
                ]);

                BankAccount::where('id', $transfer->bank_account_id)
                    ->decrement('current_balance', $amount);
            }

            return $transfer;
        });

        return redirect()
            ->route('treasury-bank-transfers.show', $transfer->id)
            ->with('success', "تم إنشاء التحويل {$transfer->transfer_no} بنجاح");
    }

    public function show(TreasuryBankTransfer $treasuryBankTransfer)
    {
        $treasuryBankTransfer->load(['treasury', 'bankAccount', 'company']);

        return view('treasury-bank-transfers.show', compact('treasuryBankTransfer'));
    }

    public function destroy(TreasuryBankTransfer $treasuryBankTransfer)
    {
        DB::transaction(function () use ($treasuryBankTransfer) {
            if ($treasuryBankTransfer->status === 'completed') {
                $amount = (float) $treasuryBankTransfer->amount;

                \App\Models\Treasury\TreasuryTransaction::where('reference_type', TreasuryBankTransfer::class)
                    ->where('reference_id', $treasuryBankTransfer->id)
                    ->each(fn($txn) => $txn->forceDelete());

                if ($treasuryBankTransfer->transfer_type === 'treasury_to_bank') {
                    BankAccount::where('id', $treasuryBankTransfer->bank_account_id)
                        ->decrement('current_balance', $amount);
                } else {
                    BankAccount::where('id', $treasuryBankTransfer->bank_account_id)
                        ->increment('current_balance', $amount);
                }
            }

            $treasuryBankTransfer->delete();
        });

        return redirect()
            ->route('treasury-bank-transfers.index')
            ->with('success', 'تم حذف التحويل بنجاح');
    }
}
