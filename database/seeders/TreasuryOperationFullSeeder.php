<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Treasury;
use App\Models\TreasuryAdjustment;
use App\Models\TreasuryAlert;
use App\Models\TreasuryCashLimit;
use App\Models\TreasuryClosingDetail;
use App\Models\TreasuryCount;
use App\Models\TreasuryCountDetail;
use App\Models\TreasuryCustody;
use App\Models\TreasuryCustodyTransaction;
use App\Models\TreasuryDailyClosing;
use App\Models\TreasuryOpeningBalance;
use App\Models\TreasuryShift;
use App\Models\TreasuryShiftTransaction;
use App\Models\TreasuryTransfer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TreasuryOperationFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $treasury = Treasury::where('company_id', $company->id)->first();
            $adminUser = User::where('company_id', $company->id)->first();

            if (!$treasury) continue;

            // Treasury Opening Balances
            TreasuryOpeningBalance::create([
                'company_id' => $company->id,
                'treasury_id' => $treasury->id,
                'opening_balance' => 50000,
            ]);

            // Treasury Shifts
            $employee = \App\Models\Employee::where('company_id', $company->id)->first();
            $shift = TreasuryShift::updateOrCreate(
                ['shift_no' => 'TS-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'company_id' => $company->id,
                    'treasury_id' => $treasury->id,
                    'cashier_id' => $employee?->id,
                    'start_datetime' => now()->subHours(8),
                    'end_datetime' => now(),
                    'opening_balance' => 50000,
                    'closing_balance' => 55000,
                    'actual_balance' => 54800,
                    'difference_amount' => -200,
                    'status' => 'CLOSED',
                ]
            );

            TreasuryShiftTransaction::create([
                'treasury_shift_id' => $shift->id,
                'transaction_type' => 'RECEIPT',
                'amount' => 10000,
                'transaction_datetime' => now()->subHours(4),
                'notes' => 'قبض من عميل',
                'reference_type' => 'Collection',
                'reference_id' => 1,
            ]);

            TreasuryShiftTransaction::create([
                'treasury_shift_id' => $shift->id,
                'transaction_type' => 'PAYMENT',
                'amount' => 5000,
                'transaction_datetime' => now()->subHours(2),
                'notes' => 'دفع لمورد',
                'reference_type' => 'PaymentVoucher',
                'reference_id' => 1,
            ]);

            // Treasury Counts
            $count = TreasuryCount::updateOrCreate(
                ['count_no' => 'TC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'treasury_shift_id' => $shift->id,
                    'count_date' => now()->toDateString(),
                    'counted_by' => $employee?->id,
                    'expected_amount' => 55000,
                    'actual_amount' => 54800,
                    'difference_amount' => -200,
                ]
            );

            TreasuryCountDetail::create([
                'treasury_count_id' => $count->id,
                'denomination' => '500',
                'qty' => 100,
                'total_amount' => 50000,
            ]);

            TreasuryCountDetail::create([
                'treasury_count_id' => $count->id,
                'denomination' => '100',
                'qty' => 48,
                'total_amount' => 4800,
            ]);

            // Treasury Transfers
            $secondTreasury = Treasury::where('company_id', $company->id)->skip(1)->first();
            if ($secondTreasury) {
                TreasuryTransfer::updateOrCreate(
                    ['transfer_no' => 'TT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'company_id' => $company->id,
                        'from_treasury_id' => $treasury->id,
                        'to_treasury_id' => $secondTreasury->id,
                        'amount' => 5000,
                        'transfer_date' => now()->toDateString(),
                        'status' => 'POSTED',
                        'notes' => 'تحويل بين الخزائن',
                    ]
                );
            }

            // Treasury Adjustments
            TreasuryAdjustment::updateOrCreate(
                ['adjustment_no' => 'TA-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'treasury_id' => $treasury->id,
                    'adjustment_date' => now()->toDateString(),
                    'adjustment_type' => 'OVERAGE',
                    'amount' => 200,
                    'reason' => 'إضافة مبلغ تم العثور عليه',
                ]
            );

            // Treasury Daily Closings
            $closing = TreasuryDailyClosing::updateOrCreate(
                ['treasury_id' => $treasury->id, 'closing_date' => now()->toDateString()],
                [
                    'opening_balance' => 50000,
                    'receipts_total' => 10000,
                    'payments_total' => 5000,
                    'expected_balance' => 55000,
                    'actual_balance' => 54800,
                    'difference_amount' => -200,
                    'status' => 'APPROVED',
                    'approved_by' => $adminUser?->id,
                ]
            );

            TreasuryClosingDetail::create([
                'treasury_daily_closing_id' => $closing->id,
                'transaction_type' => 'RECEIPT',
                'amount' => 10000,
                'reference_type' => 'Collection',
                'reference_id' => 1,
            ]);

            // Treasury Custodies
            if ($employee) {
                $custody = TreasuryCustody::updateOrCreate(
                    ['custody_no' => 'TCU-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'employee_id' => $employee->id,
                        'treasury_id' => $treasury->id,
                        'issue_date' => now()->toDateString(),
                        'amount' => 10000,
                        'status' => 'ACTIVE',
                    ]
                );

                TreasuryCustodyTransaction::create([
                    'treasury_custody_id' => $custody->id,
                    'transaction_date' => now()->toDateString(),
                    'transaction_type' => 'ISSUE',
                    'amount' => 10000,
                    'notes' => 'تسليم عهدة',
                ]);
            }

            // Treasury Cash Limits
            TreasuryCashLimit::updateOrCreate(
                ['treasury_id' => $treasury->id],
                [
                    'minimum_limit' => 10000,
                    'maximum_limit' => 100000,
                    'alert_limit' => 15000,
                ]
            );

            // Treasury Alerts
            TreasuryAlert::create([
                'treasury_id' => $treasury->id,
                'alert_type' => 'LOW_CASH',
                'alert_date' => now()->toDateString(),
                'message' => 'رصيد الخزينة أقل من الحد الأدنى',
                'status' => 'NEW',
            ]);
        }
    }
}
