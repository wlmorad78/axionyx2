<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\AccountType;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransfer;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\JournalEntryType;
use App\Models\ManualJournalEntry;
use App\Models\ManualJournalEntryLine;
use App\Models\OpeningBalance;
use App\Models\PaymentVoucher;
use App\Models\ReceiptVoucher;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountingFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedAccountGroups($company);
            $this->seedAccounts($company);
            $this->seedFiscalYear($company);
            $this->seedJournalEntries($company);
            $this->seedBankAccounts($company);
            $this->seedVouchers($company);
        }
    }

    private function seedAccountGroups(Company $company): void
    {
        $assetType = AccountType::where('code', 'ASSET')->first();
        $liabilityType = AccountType::where('code', 'LIABILITY')->first();
        $equityType = AccountType::where('code', 'EQUITY')->first();
        $revenueType = AccountType::where('code', 'REVENUE')->first();
        $expenseType = AccountType::where('code', 'EXPENSE')->first();

        $groups = [
            ['code' => 'AG-ASSET-' . $company->id, 'account_type_id' => $assetType?->id, 'name' => 'الأصول'],
            ['code' => 'AG-LIAB-' . $company->id, 'account_type_id' => $liabilityType?->id, 'name' => 'الخصوم'],
            ['code' => 'AG-EQ-' . $company->id, 'account_type_id' => $equityType?->id, 'name' => 'حقوق الملكية'],
            ['code' => 'AG-REV-' . $company->id, 'account_type_id' => $revenueType?->id, 'name' => 'الإيرادات'],
            ['code' => 'AG-EXP-' . $company->id, 'account_type_id' => $expenseType?->id, 'name' => 'المصروفات'],
        ];

        foreach ($groups as $g) {
            AccountGroup::updateOrCreate(
                ['code' => $g['code']],
                [
                    'account_type_id' => $g['account_type_id'],
                    'name' => $g['name'],
                ]
            );
        }
    }

    private function seedAccounts(Company $company): void
    {
        $assetGroup = AccountGroup::where('code', 'LIKE', 'AG-ASSET-' . $company->id)->first();
        $liabilityGroup = AccountGroup::where('code', 'LIKE', 'AG-LIAB-' . $company->id)->first();
        $revenueGroup = AccountGroup::where('code', 'LIKE', 'AG-REV-' . $company->id)->first();
        $expenseGroup = AccountGroup::where('code', 'LIKE', 'AG-EXP-' . $company->id)->first();

        $accounts = [
            ['code' => '1001-' . $company->id, 'group' => $assetGroup, 'name_ar' => 'الصندوق', 'name_en' => 'Cash', 'nature' => 'debit'],
            ['code' => '1002-' . $company->id, 'group' => $assetGroup, 'name_ar' => 'البنك', 'name_en' => 'Bank', 'nature' => 'debit'],
            ['code' => '1003-' . $company->id, 'group' => $assetGroup, 'name_ar' => 'الذمم المدينة', 'name_en' => 'Accounts Receivable', 'nature' => 'debit'],
            ['code' => '1004-' . $company->id, 'group' => $assetGroup, 'name_ar' => 'المخزون', 'name_en' => 'Inventory', 'nature' => 'debit'],
            ['code' => '2001-' . $company->id, 'group' => $liabilityGroup, 'name_ar' => 'الذمم الدائنة', 'name_en' => 'Accounts Payable', 'nature' => 'credit'],
            ['code' => '2002-' . $company->id, 'group' => $liabilityGroup, 'name_ar' => 'ضريبة القيمة المضافة', 'name_en' => 'VAT Payable', 'nature' => 'credit'],
            ['code' => '3001-' . $company->id, 'group' => $liabilityGroup, 'name_ar' => 'رأس المال', 'name_en' => 'Capital', 'nature' => 'credit'],
            ['code' => '4001-' . $company->id, 'group' => $revenueGroup, 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue', 'nature' => 'credit'],
            ['code' => '4002-' . $company->id, 'group' => $revenueGroup, 'name_ar' => 'مرتجعات المبيعات', 'name_en' => 'Sales Returns', 'nature' => 'debit'],
            ['code' => '5001-' . $company->id, 'group' => $expenseGroup, 'name_ar' => 'تكلفة المبيعات', 'name_en' => 'Cost of Goods Sold', 'nature' => 'debit'],
            ['code' => '5002-' . $company->id, 'group' => $expenseGroup, 'name_ar' => 'الرواتب', 'name_en' => 'Salaries', 'nature' => 'debit'],
            ['code' => '5003-' . $company->id, 'group' => $expenseGroup, 'name_ar' => 'الإيجار', 'name_en' => 'Rent', 'nature' => 'debit'],
            ['code' => '5004-' . $company->id, 'group' => $expenseGroup, 'name_ar' => 'المرافق', 'name_en' => 'Utilities', 'nature' => 'debit'],
        ];

        foreach ($accounts as $a) {
            Account::updateOrCreate(
                ['company_id' => $company->id, 'account_code' => $a['code']],
                [
                    'account_group_id' => $a['group']?->id,
                    'account_name' => $a['name_ar'],
                    'is_leaf' => true,
                    'allow_transactions' => true,
                    'status' => 'active',
                ]
            );
        }
    }

    private function seedFiscalYear(Company $company): void
    {
        $fy = FiscalYear::updateOrCreate(
            ['company_id' => $company->id, 'year_code' => 'FY-2026-' . $company->id],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => false,
            ]
        );

        AccountingPeriod::updateOrCreate(
            ['fiscal_year_id' => $fy->id, 'period_no' => 1, 'period_name' => 'يناير 2026'],
            ['start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'is_closed' => true]
        );

        AccountingPeriod::updateOrCreate(
            ['fiscal_year_id' => $fy->id, 'period_no' => 2, 'period_name' => 'فبراير 2026'],
            ['start_date' => '2026-02-01', 'end_date' => '2026-02-28', 'is_closed' => true]
        );

        OpeningBalance::updateOrCreate(
            ['company_id' => $company->id, 'fiscal_year_id' => $fy->id, 'account_id' => Account::where('account_code', 'LIKE', '3001-' . $company->id)->first()?->id],
            [
                'opening_debit' => 0,
                'opening_credit' => 500000,
            ]
        );
    }

    private function seedJournalEntries(Company $company): void
    {
        $salesJEType = JournalEntryType::where('code', 'SI')->first();
        $purchaseJEType = JournalEntryType::where('code', 'PI')->first();
        $cashJEType = JournalEntryType::where('code', 'JV')->first();

        $cashAccount = Account::where('account_code', 'LIKE', '1001-' . $company->id)->first();
        $arAccount = Account::where('account_code', 'LIKE', '1003-' . $company->id)->first();
        $salesRevenue = Account::where('account_code', 'LIKE', '4001-' . $company->id)->first();
        $apAccount = Account::where('account_code', 'LIKE', '2001-' . $company->id)->first();
        $purchasesAccount = Account::where('account_code', 'LIKE', '5001-' . $company->id)->first();

        // Sales Journal Entry
        $je = JournalEntry::updateOrCreate(
            ['company_id' => $company->id, 'entry_no' => 'JE-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
            [
                'journal_entry_type_id' => $salesJEType?->id,
                'entry_date' => now()->subDays(10)->toDateString(),
                'description' => 'قيد مبيعات',
                'total_debit' => 11400,
                'total_credit' => 11400,
                'status' => 'posted',
                'reference_type' => 'SalesInvoice',
                'reference_id' => 1,
            ]
        );

        if ($arAccount && $salesRevenue) {
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $arAccount->id,
                'debit' => 11400,
                'credit' => 0,
                'description' => 'مدين - ذمم مدينة',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $salesRevenue->id,
                'debit' => 0,
                'credit' => 11400,
                'description' => 'دائن - إيرادات مبيعات',
            ]);
        }

        // Purchase Journal Entry
        $je2 = JournalEntry::updateOrCreate(
            ['company_id' => $company->id, 'entry_no' => 'JE-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-002'],
            [
                'journal_entry_type_id' => $purchaseJEType?->id,
                'entry_date' => now()->subDays(5)->toDateString(),
                'description' => 'قيد مشتريات',
                'total_debit' => 25000,
                'total_credit' => 25000,
                'status' => 'posted',
                'reference_type' => 'PurchaseInvoice',
                'reference_id' => 1,
            ]
        );

        if ($purchasesAccount && $apAccount) {
            JournalEntryLine::create([
                'journal_entry_id' => $je2->id,
                'account_id' => $purchasesAccount->id,
                'debit' => 25000,
                'credit' => 0,
                'description' => 'مدين - تكلفة مبيعات',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $je2->id,
                'account_id' => $apAccount->id,
                'debit' => 0,
                'credit' => 25000,
                'description' => 'دائن - ذمم دائنة',
            ]);
        }

        // Manual Journal Entry
        $mje = ManualJournalEntry::updateOrCreate(
            ['company_id' => $company->id, 'entry_no' => 'MJE-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
            [
                'entry_date' => now()->toDateString(),
                'description' => 'قيد يومية عام - تعديل',
                'status' => 'posted',
                'created_by' => User::where('company_id', $company->id)->first()?->id,
            ]
        );

        if ($cashAccount && $arAccount) {
            ManualJournalEntryLine::create([
                'manual_journal_entry_id' => $mje->id,
                'account_id' => $cashAccount->id,
                'debit' => 5000,
                'credit' => 0,
                'description' => 'قبض من عميل',
            ]);

            ManualJournalEntryLine::create([
                'manual_journal_entry_id' => $mje->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => 5000,
                'description' => 'تخصيص على الذمم المدينة',
            ]);
        }
    }

    private function seedBankAccounts(Company $company): void
    {
        $bankAccount = BankAccount::updateOrCreate(
            ['company_id' => $company->id, 'account_number' => '1234567890-' . $company->id],
            [
                'bank_name' => 'البنك الأهلي المصري',
                'branch_name' => 'فرع الإسكندرية',
                'currency_id' => \App\Models\Currency::where('code', 'EGP')->first()?->id,
                'opening_balance' => 100000,
                'current_balance' => 150000,
                'is_active' => true,
            ]
        );

        BankTransfer::updateOrCreate(
            ['company_id' => $company->id, 'transfer_no' => 'BT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
            [
                'from_bank_account_id' => $bankAccount->id,
                'to_bank_account_id' => $bankAccount->id,
                'amount' => 10000,
                'transfer_date' => now()->subDays(5)->toDateString(),
                'notes' => 'تحويل داخلي',
            ]
        );

        BankReconciliation::updateOrCreate(
            ['bank_account_id' => $bankAccount->id, 'reconciliation_date' => now()->subDay()->toDateString()],
            [
                'statement_balance' => 148000,
                'system_balance' => 150000,
                'difference' => 2000,
            ]
        );
    }

    private function seedVouchers(Company $company): void
    {
        $customer = Customer::where('company_id', $company->id)->first();
        $cashAccount = Account::where('account_code', 'LIKE', '1001-' . $company->id)->first();

        if ($customer) {
            ReceiptVoucher::updateOrCreate(
                ['company_id' => $company->id, 'voucher_no' => 'RV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'customer_id' => $customer->id,
                    'amount' => 5000,
                    'voucher_date' => now()->subDays(3)->toDateString(),
                ]
            );

            $supplier = \App\Models\Supplier::where('company_id', $company->id)->first();
            if ($supplier) {
                PaymentVoucher::updateOrCreate(
                    ['company_id' => $company->id, 'voucher_no' => 'PV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'supplier_id' => $supplier->id,
                        'amount' => 3000,
                        'voucher_date' => now()->subDays(2)->toDateString(),
                    ]
                );
            }
        }
    }
}
