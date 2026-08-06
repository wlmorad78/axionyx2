<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Item;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Treasury;
use App\Models\Account;
use App\Models\PaymentVoucher;
use App\Models\ReceiptVoucher;
use App\Models\InventoryTransaction;
use App\Models\JournalEntry;
use App\Models\Employee;
use App\Models\OpeningBalanceDocument;
use App\Models\CustomerGroup;
use App\Models\SupplierGroup;
use App\Models\PriceList;
use App\Models\ProductCompany;
use App\Observers\SalesInvoiceObserver;
use App\Observers\PurchaseInvoiceObserver;
use App\Observers\AuditObserver;
use App\Services\PermissionService;
use App\Services\UnitConversionService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(UnitConversionService::class);
    }

    public function boot(): void
    {
        // ─── Document Observers (business logic) ───
        SalesInvoice::observe(SalesInvoiceObserver::class);
        PurchaseInvoice::observe(PurchaseInvoiceObserver::class);

        // ─── Universal Audit Observer ───
        $auditableModels = [
            Company::class,
            Customer::class,
            Supplier::class,
            Item::class,
            Branch::class,
            Warehouse::class,
            Treasury::class,
            Account::class,
            PaymentVoucher::class,
            ReceiptVoucher::class,
            InventoryTransaction::class,
            JournalEntry::class,
            SalesInvoice::class,
            PurchaseInvoice::class,
            Employee::class,
            OpeningBalanceDocument::class,
            CustomerGroup::class,
            SupplierGroup::class,
            PriceList::class,
            ProductCompany::class,
        ];

        foreach ($auditableModels as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
