<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RepDailySettlement;
use App\Models\Treasury\Treasury;
use App\Models\Treasury\TreasuryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepDailySettlementController extends Controller
{
    public function index(Request $request)
    {
        $query = RepDailySettlement::with(['salesRep', 'expenses', 'createdBy', 'approvedBy', 'salesmanDebt']);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('settlement_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('settlement_date', '<=', $request->date_to);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('settlement_no', 'like', "%$s%");
        }

        return $query->orderByDesc('settlement_date')->paginate($request->per_page ?? 15);
    }

    public function show(RepDailySettlement $repDailySettlement)
    {
        return $repDailySettlement->load(['salesRep', 'expenses', 'issueOrder', 'createdBy', 'approvedBy', 'salesmanDebt']);
    }

    public function approve(Request $request, RepDailySettlement $repDailySettlement)
    {
        if ($repDailySettlement->status === 'approved') {
            return response()->json(['message' => 'التسوية معتمدة بالفعل'], 422);
        }

        DB::beginTransaction();

        try {
            $repDailySettlement->update([
                'status' => 'approved',
                'approved_by' => $request->user()->employee_id ?? null,
            ]);

            $actualCash = (float) $repDailySettlement->actual_cash;

            if ($actualCash > 0) {
                $existing = TreasuryTransaction::where('reference_type', RepDailySettlement::class)
                    ->where('reference_id', $repDailySettlement->id)
                    ->first();

                if (!$existing) {
                    $treasuryId = $this->resolveTreasury($repDailySettlement);

                    if ($treasuryId) {
                        $repName = $repDailySettlement->salesRep?->name ?? 'مندوب';
                        TreasuryTransaction::create([
                            'company_id' => $repDailySettlement->company_id,
                            'treasury_id' => $treasuryId,
                            'type' => 'credit',
                            'amount' => $actualCash,
                            'reference_type' => RepDailySettlement::class,
                            'reference_id' => $repDailySettlement->id,
                            'description' => "تسوية مندوب {$repName} - {$repDailySettlement->settlement_no}",
                            'transaction_date' => $repDailySettlement->settlement_date,
                            'created_by' => $request->user()->employee_id ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'تم اعتماد التسوية وتسجيل المبلغ في الخزنة بنجاح',
                'data' => $repDailySettlement,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, RepDailySettlement $repDailySettlement)
    {
        if ($repDailySettlement->status === 'cancelled') {
            return response()->json(['message' => 'التسوية ملغاة بالفعل'], 422);
        }

        DB::beginTransaction();

        try {
            TreasuryTransaction::where('reference_type', RepDailySettlement::class)
                ->where('reference_id', $repDailySettlement->id)
                ->forceDelete();

            if ($repDailySettlement->salesman_debt_id) {
                $debt = \App\Models\Sales\SalesmanDebt::find($repDailySettlement->salesman_debt_id);
                if ($debt && $debt->total_paid == 0) {
                    $account = \App\Models\Sales\SalesmanAccount::find($debt->salesman_account_id);
                    if ($account) {
                        $account->update([
                            'total_debts' => max(0, $account->total_debts - $debt->gross_debt),
                            'current_balance' => max(0, $account->current_balance - $debt->gross_debt),
                        ]);

                        \App\Models\Sales\SalesmanAccountMovement::where('reference_type', \App\Models\Sales\SalesmanDebt::class)
                            ->where('reference_id', $debt->id)
                            ->forceDelete();
                    }
                    $debt->forceDelete();
                }
            }

            $repDailySettlement->update([
                'status' => 'cancelled',
                'salesman_debt_id' => null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إلغاء التسوية بنجاح',
                'data' => $repDailySettlement,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ: ' . $e->getMessage()], 500);
        }
    }

    public function reopen(Request $request, RepDailySettlement $repDailySettlement)
    {
        if ($repDailySettlement->status !== 'cancelled' && $repDailySettlement->status !== 'approved') {
            return response()->json(['message' => 'يمكن إعادة فتح التسوية الملغاة أو المعتمدة فقط'], 422);
        }

        DB::beginTransaction();

        try {
            if ($repDailySettlement->status === 'approved') {
                TreasuryTransaction::where('reference_type', RepDailySettlement::class)
                    ->where('reference_id', $repDailySettlement->id)
                    ->forceDelete();
            }

            $repDailySettlement->update([
                'status' => 'submitted',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إعادة فتح التسوية بنجاح',
                'data' => $repDailySettlement,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ: ' . $e->getMessage()], 500);
        }
    }

    private function resolveTreasury(RepDailySettlement $settlement): ?int
    {
        $assignment = DB::table('salesman_assignments')
            ->where('employee_id', $settlement->sales_rep_id)
            ->where('is_active', true)
            ->where('job_role', 'salesman')
            ->first();

        if ($assignment && $assignment->sales_territory_id) {
            $territory = DB::table('sales_territories')
                ->where('id', $assignment->sales_territory_id)
                ->first();

            if ($territory && $territory->treasury_id) {
                return (int) $territory->treasury_id;
            }

            if ($territory && $territory->branch_id) {
                $treasury = Treasury::where('branch_id', $territory->branch_id)
                    ->where('is_main', true)
                    ->where('is_active', true)
                    ->first();

                if ($treasury) {
                    return $treasury->id;
                }

                $treasury = Treasury::where('branch_id', $territory->branch_id)
                    ->where('is_active', true)
                    ->first();

                if ($treasury) {
                    return $treasury->id;
                }
            }
        }

        $treasury = Treasury::where('company_id', $settlement->company_id)
            ->where('is_main', true)
            ->where('is_active', true)
            ->first();

        return $treasury?->id;
    }
}
