<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Representative;
use App\Models\SalesTerritory;
use App\Models\SubscriptionPlan;
use App\Models\CompanySubscription;
use App\Models\Account;
use App\Models\Treasury;
use App\Models\BankAccount;
use App\Models\TaxType;
use App\Models\Currency;
use App\Models\BankReconciliation;
use App\Models\BankTransfer;
use App\Models\FiscalYear;
use App\Models\Governorate;
use App\Models\City;
use App\Models\District;
use App\Models\Street;
use App\Models\Department;
use App\Models\JobPosition;
use App\Models\Employee;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Model;

class ScreenDataResolver
{
    private static array $map = [
        'company-settings'  => Company::class,
        'customers'         => Customer::class,
        'products'          => Item::class,
        'suppliers'         => Supplier::class,
        'users'             => User::class,
        'warehouses'        => Warehouse::class,
        'roles'             => Role::class,
        'representatives'   => Representative::class,
        'sales-territories' => SalesTerritory::class,
        'subscription-plans'=> SubscriptionPlan::class,
        'company-subscriptions' => CompanySubscription::class,
        'chart-of-accounts' => Account::class,
        'cashboxes'         => Treasury::class,
        'banks'             => BankAccount::class,
        'bank-accounts'     => BankAccount::class,
        'bank-transfers'    => BankTransfer::class,
        'bank-reconciliations' => BankReconciliation::class,
        'tax-types'         => TaxType::class,
        'currencies'        => Currency::class,
        'fiscal-years'      => FiscalYear::class,
        'governorates'      => Governorate::class,
        'cities'            => City::class,
        'districts'         => District::class,
        'streets'           => Street::class,
        'departments'       => Department::class,
        'job-positions'     => JobPosition::class,
        'employees'         => Employee::class,
        'attendance'        => AttendanceRecord::class,
        'leaves'            => LeaveRequest::class,
        'payroll'           => PayrollRun::class,
    ];

    public static function resolve(string $screenKey): ?string
    {
        return self::$map[$screenKey] ?? null;
    }

    public static function getColumns(string $screenKey): array
    {
        $class = self::resolve($screenKey);
        if (!$class || !class_exists($class)) {
            return [];
        }

        return match ($screenKey) {
            'company-settings' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name_ar', 'label' => 'الاسم بالعربي'],
                ['key' => 'name_en', 'label' => 'الاسم بالإنجليزي'],
                ['key' => 'tax_number', 'label' => 'الرقم الضريبي'],
                ['key' => 'phone', 'label' => 'التليفون'],
                ['key' => 'email', 'label' => 'البريد الإلكتروني'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'customers' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'phone', 'label' => 'التليفون'],
                ['key' => 'mobile', 'label' => 'الموبايل'],
                ['key' => 'current_balance', 'label' => 'الرصيد'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'products' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'barcode', 'label' => 'الباركود'],
                ['key' => 'purchase_price', 'label' => 'سعر الشراء'],
                ['key' => 'sale_price', 'label' => 'سعر البيع'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'suppliers' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'phone', 'label' => 'التليفون'],
                ['key' => 'mobile', 'label' => 'الموبايل'],
                ['key' => 'current_balance', 'label' => 'الرصيد'],
            ],
            'users' => [
                ['key' => 'usercode', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'phone', 'label' => 'التليفون'],
                ['key' => 'email', 'label' => 'البريد'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'warehouses' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'subscription-plans' => [
                ['key' => 'code', 'label' => 'الكود'],
                ['key' => 'name', 'label' => 'الاسم'],
                ['key' => 'tier', 'label' => 'المستوى'],
                ['key' => 'monthly_price', 'label' => 'السعر الشهري'],
                ['key' => 'max_users', 'label' => 'الحد الأقصى للمستخدمين'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'bank-accounts' => [
                ['key' => 'bank_name', 'label' => 'اسم البنك'],
                ['key' => 'account_number', 'label' => 'رقم الحساب'],
                ['key' => 'branch_name', 'label' => 'الفرع'],
                ['key' => 'current_balance', 'label' => 'الرصيد'],
                ['key' => 'is_active', 'label' => 'الحالة', 'type' => 'boolean'],
            ],
            'bank-transfers' => [
                ['key' => 'transfer_no', 'label' => 'رقم التحويل'],
                ['key' => 'transfer_date', 'label' => 'التاريخ'],
                ['key' => 'amount', 'label' => 'المبلغ'],
                ['key' => 'status', 'label' => 'الحالة'],
            ],
            'bank-reconciliations' => [
                ['key' => 'reconciliation_date', 'label' => 'تاريخ التسوية'],
                ['key' => 'statement_balance', 'label' => 'رصيد كشف الحساب'],
                ['key' => 'difference', 'label' => 'الفرق'],
                ['key' => 'status', 'label' => 'الحالة'],
            ],
            default => self::getDefaultColumns($class),
       };
    }

    private static function getDefaultColumns(string $class): array
    {
        $instance = new $class();
        $fillable = $instance->getFillable();
        $columns = [];

        foreach (array_slice($fillable, 0, 5) as $field) {
            $columns[] = ['key' => $field, 'label' => $field];
        }

        return $columns;
    }
}
