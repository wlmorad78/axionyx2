<?php

use App\Http\Controllers\DemoAuthController;
use App\Http\Controllers\Api\Settings\SubscriptionPlanController;
use App\Http\Controllers\Web\BankAccountWebController;
use App\Http\Controllers\Web\BankReconciliationWebController;
use App\Http\Controllers\Web\BankTransferWebController;
use App\Http\Controllers\Web\ClearDataController;
use App\Http\Controllers\Web\ItemLedgerController;
use App\Http\Controllers\Web\LoadRequestWebController;
use App\Http\Controllers\Web\ReturnOrderWebController;
use App\Http\Controllers\Web\VehicleWebController;
use App\Models\AdminModule;
use App\Models\AdminScreen;
use App\Models\CompanySubscription;
use App\Support\ScreenDataResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $companyId = session('company_id') ?? (auth()->user()?->company_id ?? null);

    $treasuryBankTransfers = \App\Models\TreasuryBankTransfer::with(['treasury', 'bankAccount'])
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->orderByDesc('id')->limit(10)->get();

    $bankSupplierPayments = \App\Models\BankSupplierPayment::with(['bankAccount', 'supplier'])
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->orderByDesc('id')->limit(10)->get();

    $totalTreasuryToBank = \App\Models\TreasuryBankTransfer::where('transfer_type', 'treasury_to_bank')
        ->where('status', 'completed')->when($companyId, fn($q) => $q->where('company_id', $companyId))->sum('amount');

    $totalBankToTreasury = \App\Models\TreasuryBankTransfer::where('transfer_type', 'bank_to_treasury')
        ->where('status', 'completed')->when($companyId, fn($q) => $q->where('company_id', $companyId))->sum('amount');

    $totalBankToSupplier = \App\Models\BankSupplierPayment::where('status', 'completed')
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))->sum('amount');

    return view('dashboard', compact(
        'treasuryBankTransfers', 'bankSupplierPayments',
        'totalTreasuryToBank', 'totalBankToTreasury', 'totalBankToSupplier'
    ));
})->name('web.dashboard');

Route::get('/admin', function () {
    $user = auth()->user();
    $companyId = session('company_id') ?? ($user?->company_id ?? null);

    $query = AdminModule::query()
        ->where('is_active', true)
        ->with(['screens' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
        ->orderBy('sort_order');

    if ($companyId) {
        $subscription = CompanySubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            $allowedModuleIds = DB::table('plan_modules')
                ->where('subscription_plan_id', $subscription->subscription_plan_id)
                ->where('can_view', true)
                ->pluck('module_id')
                ->toArray();

            $query->whereIn('admin_modules.id', $allowedModuleIds);
        }
    }

    $modules = $query->get();

    return view('admin.index', compact('modules'));
})->name('web.admin');

Route::get('/admin/screens/{key}', function (string $key) {
    $screen = AdminScreen::query()
        ->where('key', $key)
        ->where('is_active', true)
        ->with('children')
        ->firstOrFail();

    $user = auth()->user();
    $companyId = session('company_id') ?? ($user?->company_id ?? null);

    if ($companyId) {
        $subscription = CompanySubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if ($subscription) {
            $moduleId = $screen->module_id;
            $hasAccess = DB::table('plan_modules')
                ->where('subscription_plan_id', $subscription->subscription_plan_id)
                ->where('module_id', $moduleId)
                ->where('can_view', true)
                ->exists();

            if (!$hasAccess) {
                abort(403, 'هذه الشاشة غير متاحة في باقتك الحالية. يرجى ترقية الباقة.');
            }
        }
    }

    if ($screen->screen_type === 'dashboard') {
        return view('admin.dashboard', compact('screen'));
    }

    $modelClass = ScreenDataResolver::resolve($screen->key);
    $records = collect();
    $columns = ScreenDataResolver::getColumns($screen->key);
    $totalCount = 0;

    if ($modelClass && class_exists($modelClass)) {
        $query = $modelClass::query();

        if (in_array('company_id', (new $modelClass)->getFillable())) {
            $companyId = session('company_id') ?? auth()->user()?->company_id;
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        }

        $totalCount = $query->count();
        $records = $query->orderByDesc($modelClass::CREATED_AT ?? 'id')->paginate(15);
    }

    return view('admin.resource', compact('screen', 'records', 'columns', 'totalCount'));
})->name('web.admin.screens');

Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('web.subscription-plans.index');
Route::get('/subscription-plans/{id}', [SubscriptionPlanController::class, 'show'])->name('web.subscription-plans.show');
Route::post('/subscription-plans/{id}/assign', [SubscriptionPlanController::class, 'assign'])->name('web.subscription-plans.assign');

Route::get('/switch-company/{companyId}', function ($companyId) {
    session(['company_id' => $companyId]);
    return back()->with('success', 'تم تبديل الشركة بنجاح.');
})->name('web.switch-company');

Route::get('/demo-login', [DemoAuthController::class, 'showLogin'])->name('web.demo-login');
Route::post('/demo-login', [DemoAuthController::class, 'login'])->name('web.demo-login.post');
Route::get('/demo-logout', [DemoAuthController::class, 'logout'])->name('web.demo-logout');

Route::middleware(['auth'])->prefix('load-requests')->name('web.load-requests.')->group(function () {
    Route::get('/', [LoadRequestWebController::class, 'index'])->name('index');
    Route::get('/create', [LoadRequestWebController::class, 'create'])->name('create');
    Route::post('/', [LoadRequestWebController::class, 'store'])->name('store');
    Route::get('/{loadRequest}/complementary/create', [LoadRequestWebController::class, 'createComplementary'])->name('complementary.create');
    Route::post('/{loadRequest}/complementary', [LoadRequestWebController::class, 'storeComplementary'])->name('complementary.store');
    Route::get('/{loadRequest}', [LoadRequestWebController::class, 'show'])->name('show');
    Route::get('/{loadRequest}/approve', [LoadRequestWebController::class, 'approve'])->name('approve');
    Route::patch('/{loadRequest}/approval', [LoadRequestWebController::class, 'processApproval'])->name('approval');
    Route::post('/{loadRequest}/cancel', [LoadRequestWebController::class, 'cancel'])->name('cancel');
    Route::delete('/{loadRequest}', [LoadRequestWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('vehicles')->name('web.vehicles.')->group(function () {
    Route::get('/', [VehicleWebController::class, 'index'])->name('index');
    Route::get('/create', [VehicleWebController::class, 'create'])->name('create');
    Route::post('/', [VehicleWebController::class, 'store'])->name('store');
    Route::get('/{vehicle}', [VehicleWebController::class, 'show'])->name('show');
    Route::get('/{vehicle}/edit', [VehicleWebController::class, 'edit'])->name('edit');
    Route::put('/{vehicle}', [VehicleWebController::class, 'update'])->name('update');
    Route::delete('/{vehicle}', [VehicleWebController::class, 'destroy'])->name('destroy');
    Route::post('/{vehicle}/restore', [VehicleWebController::class, 'restore'])->name('restore');
});

Route::middleware(['auth'])->prefix('return-orders')->name('web.return-orders.')->group(function () {
    Route::get('/', [ReturnOrderWebController::class, 'index'])->name('index');
    Route::get('/{returnOrder}', [ReturnOrderWebController::class, 'show'])->name('show');
    Route::get('/{returnOrder}/approve', [ReturnOrderWebController::class, 'approve'])->name('approve');
    Route::patch('/{returnOrder}/process', [ReturnOrderWebController::class, 'processApproval'])->name('process');
    Route::patch('/{returnOrder}/reopen', [ReturnOrderWebController::class, 'reopen'])->name('reopen');
});

Route::middleware(['auth'])->prefix('bank-accounts')->name('web.bank-accounts.')->group(function () {
    Route::get('/', [BankAccountWebController::class, 'index'])->name('index');
    Route::get('/create', [BankAccountWebController::class, 'create'])->name('create');
    Route::post('/', [BankAccountWebController::class, 'store'])->name('store');
    Route::get('/{bankAccount}', [BankAccountWebController::class, 'show'])->name('show');
    Route::get('/{bankAccount}/edit', [BankAccountWebController::class, 'edit'])->name('edit');
    Route::put('/{bankAccount}', [BankAccountWebController::class, 'update'])->name('update');
    Route::delete('/{bankAccount}', [BankAccountWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('bank-transfers')->name('web.bank-transfers.')->group(function () {
    Route::get('/', [BankTransferWebController::class, 'index'])->name('index');
    Route::get('/create', [BankTransferWebController::class, 'create'])->name('create');
    Route::post('/', [BankTransferWebController::class, 'store'])->name('store');
    Route::get('/{bankTransfer}', [BankTransferWebController::class, 'show'])->name('show');
    Route::delete('/{bankTransfer}', [BankTransferWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('bank-reconciliations')->name('web.bank-reconciliations.')->group(function () {
    Route::get('/', [BankReconciliationWebController::class, 'index'])->name('index');
    Route::get('/create', [BankReconciliationWebController::class, 'create'])->name('create');
    Route::post('/', [BankReconciliationWebController::class, 'store'])->name('store');
    Route::get('/{bankReconciliation}', [BankReconciliationWebController::class, 'show'])->name('show');
    Route::delete('/{bankReconciliation}', [BankReconciliationWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('treasury-bank-transfers')->name('web.treasury-bank-transfers.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\TreasuryBankTransferWebController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\TreasuryBankTransferWebController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\TreasuryBankTransferWebController::class, 'store'])->name('store');
    Route::get('/{treasuryBankTransfer}', [\App\Http\Controllers\Web\TreasuryBankTransferWebController::class, 'show'])->name('show');
    Route::delete('/{treasuryBankTransfer}', [\App\Http\Controllers\Web\TreasuryBankTransferWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('bank-supplier-payments')->name('web.bank-supplier-payments.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\BankSupplierPaymentWebController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\BankSupplierPaymentWebController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\BankSupplierPaymentWebController::class, 'store'])->name('store');
    Route::get('/{bankSupplierPayment}', [\App\Http\Controllers\Web\BankSupplierPaymentWebController::class, 'show'])->name('show');
    Route::delete('/{bankSupplierPayment}', [\App\Http\Controllers\Web\BankSupplierPaymentWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('item-ledger')->name('web.item-ledger.')->group(function () {
    Route::get('/', [ItemLedgerController::class, 'index'])->name('index');
    Route::get('/rep-drawer/{repId}', [ItemLedgerController::class, 'repDrawer'])->name('rep-drawer');
});

Route::middleware(['auth'])->prefix('opening-balances')->name('web.opening-balances.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'store'])->name('store');
    Route::get('/{openingBalance}', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'show'])->name('show');
    Route::get('/{openingBalance}/edit', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'edit'])->name('edit');
    Route::put('/{openingBalance}', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'update'])->name('update');
    Route::delete('/{openingBalance}', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'destroy'])->name('destroy');
    Route::post('/{openingBalance}/post', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'post'])->name('post');
    Route::post('/{openingBalance}/cancel', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'cancel'])->name('cancel');
    Route::post('/{openingBalance}/restore', [\App\Http\Controllers\Web\OpeningBalanceDocumentWebController::class, 'restore'])->name('restore');
});

Route::middleware(['auth'])->prefix('treasury-opening-balances')->name('web.treasury-opening-balances.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'store'])->name('store');
    Route::get('/{treasuryOpeningBalance}', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'show'])->name('show');
    Route::get('/{treasuryOpeningBalance}/edit', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'edit'])->name('edit');
    Route::put('/{treasuryOpeningBalance}', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'update'])->name('update');
    Route::delete('/{treasuryOpeningBalance}', [\App\Http\Controllers\Web\TreasuryOpeningBalanceWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('bank-opening-balances')->name('web.bank-opening-balances.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'store'])->name('store');
    Route::get('/{bankOpeningBalance}', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'show'])->name('show');
    Route::get('/{bankOpeningBalance}/edit', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'edit'])->name('edit');
    Route::put('/{bankOpeningBalance}', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'update'])->name('update');
    Route::delete('/{bankOpeningBalance}', [\App\Http\Controllers\Web\BankOpeningBalanceWebController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth'])->prefix('admin/clear-data')->name('web.admin.clear-data.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\ClearDataController::class, 'index'])->name('index');
    Route::post('/group/{group}', [\App\Http\Controllers\Web\ClearDataController::class, 'clearGroup'])->name('group');
    Route::post('/table/{table}', [\App\Http\Controllers\Web\ClearDataController::class, 'clearTable'])->name('table');
    Route::post('/all', [\App\Http\Controllers\Web\ClearDataController::class, 'clearAll'])->name('all');
});
