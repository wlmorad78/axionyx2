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
