<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Bonus;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeSalary;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Support\HrStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function process(PayrollRun $run, int $userId): PayrollRun
    {
        if ($run->status !== HrStatus::PAYROLL_DRAFT) {
            throw ValidationException::withMessages([
                'status' => ['يمكن معالجة مسير الرواتب في حالة المسودة فقط'],
            ]);
        }

        $periodStart = Carbon::create($run->period_year, $run->period_month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return DB::transaction(function () use ($run, $userId, $periodStart, $periodEnd) {
            $run->items()->forceDelete();

            $employees = Employee::query()
                ->where('is_active', true)
                ->where('employment_status', 'active')
                ->get();

            foreach ($employees as $employee) {
                $salary = EmployeeSalary::query()
                    ->where('user_id', $employee->id)
                    ->where('is_active', true)
                    ->where('effective_from', '<=', $periodEnd)
                    ->where(function ($query) use ($periodStart) {
                        $query->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $periodStart);
                    })
                    ->orderByDesc('effective_from')
                    ->first();

                if (! $salary) {
                    continue;
                }

                $allowances = $salary->housing_allowance
                    + $salary->transport_allowance
                    + $salary->other_allowances;

                $deductions = $salary->social_insurance
                    + $salary->income_tax
                    + $salary->other_deductions;

                $bonusAmount = Bonus::query()
                    ->where('user_id', $employee->id)
                    ->where('status', HrStatus::BONUS_APPROVED)
                    ->whereBetween('bonus_date', [$periodStart, $periodEnd])
                    ->sum('amount');

                $advanceDeduction = EmployeeAdvance::query()
                    ->where('user_id', $employee->id)
                    ->where('status', HrStatus::ADVANCE_ACTIVE)
                    ->where('remaining_amount', '>', 0)
                    ->get()
                    ->sum(fn ($advance) => min(
                        (float) $advance->monthly_installment,
                        (float) $advance->remaining_amount
                    ));

                $attendance = AttendanceRecord::query()
                    ->where('user_id', $employee->id)
                    ->whereBetween('attendance_date', [$periodStart, $periodEnd])
                    ->get();

                $presentDays = $attendance->whereIn('status', [
                    HrStatus::ATTENDANCE_PRESENT,
                    HrStatus::ATTENDANCE_LATE,
                ])->count();

                $absentDays = $attendance->where('status', HrStatus::ATTENDANCE_ABSENT)->count();
                $lateDays = $attendance->where('status', HrStatus::ATTENDANCE_LATE)->count();

                $leaveDays = LeaveRequest::query()
                    ->where('user_id', $employee->id)
                    ->where('status', HrStatus::LEAVE_APPROVED)
                    ->where(function ($query) use ($periodStart, $periodEnd) {
                        $query->whereBetween('start_date', [$periodStart, $periodEnd])
                            ->orWhereBetween('end_date', [$periodStart, $periodEnd]);
                    })
                    ->sum('days_count');

                $netSalary = $salary->basic_salary + $allowances - $deductions + $bonusAmount - $advanceDeduction;

                PayrollItem::create([
                    'payroll_run_id' => $run->id,
                    'user_id' => $employee->id,
                    'basic_salary' => $salary->basic_salary,
                    'total_allowances' => $allowances,
                    'total_deductions' => $deductions,
                    'bonus_amount' => $bonusAmount,
                    'advance_deduction' => $advanceDeduction,
                    'net_salary' => max(0, $netSalary),
                    'working_days' => $run->working_days,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'leave_days' => $leaveDays,
                    'late_days' => $lateDays,
                ]);
            }

            $run->update([
                'status' => HrStatus::PAYROLL_PROCESSED,
                'processed_at' => now(),
                'processed_by' => $userId,
            ]);

            return $run->fresh()->load(['processor', 'approver', 'items.employee.user']);
        });
    }
}
