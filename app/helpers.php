<?php

if (!function_exists('hasActiveFilters')) {
    function hasActiveFilters() {
        return request()->filled('status') || 
               request()->filled('balance_type') || 
               request()->filled('branch_id') ||
               request()->filled('account_id') ||
               request()->filled('date_from') ||
               request()->filled('date_to') ||
               request()->filled('min_amount') ||
               request()->filled('max_amount') ||
               request()->filled('search') ||
               request()->filled('bank_account_id') ||
               request()->filled('treasury_id') ||
               request()->filled('fiscal_year_id') ||
               request()->filled('min_balance') ||
               request()->filled('max_balance');
    }
}

if (!function_exists('resolveEmployee')) {
    function resolveEmployee(\Illuminate\Http\Request $request) {
        $salesmanUserId = $request->input('_salesman_id') ?? $request->header('X-Salesman-Id');

        if ($salesmanUserId) {
            $salesmanUser = \App\Models\User::find($salesmanUserId);

            if ($salesmanUser) {
                // لا توجد علاقة مباشرة بين المستخدم والموظف — يُحل الموظف عبر البريد أو المندوب.
                $employee = \App\Models\Employee::where('email', $salesmanUser->email)->first();

                if (!$employee) {
                    $representative = \App\Models\Representative::where('user_id', $salesmanUser->id)->first();

                    if ($representative) {
                        $employee = \App\Models\Employee::where(
                            'national_id',
                            $representative->code
                        )->first();
                    }
                }

                return $employee;
            }
        }

        return null;
    }
}

if (!function_exists('calculateCustomerBalance')) {
    function calculateCustomerBalance($customerId, $companyId) {
        $ledger = \Illuminate\Support\Facades\DB::table('customer_ledger')
            ->where('customer_id', $customerId)
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $standalone = \Illuminate\Support\Facades\DB::table('collections')
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNull('sales_invoice_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->first();

        $debit = (float) $ledger->debit;
        $credit = (float) $ledger->credit;
        $standaloneCredit = (float) $standalone->total;

        return round($debit - $credit + $standaloneCredit, 2);
    }
}
