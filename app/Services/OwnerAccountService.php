<?php

namespace App\Services;

use App\Models\OwnerAccount\OwnerTransaction;
use App\Models\Treasury\Treasury;
use App\Models\Treasury\TreasuryTransaction;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\Item;
use App\Models\Accounting\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\JournalEntryType;
use Illuminate\Support\Facades\DB;

class OwnerAccountService
{
    /**
     * إيداع أموال من المالك للخزينة
     */
    public static function depositCash(
        int $companyId,
        int $branchId,
        int $treasuryId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        ?string $transactionDate = null
    ): OwnerTransaction {
        return DB::transaction(function () use ($companyId, $branchId, $treasuryId, $amount, $description, $createdBy, $transactionDate) {
            // 1. إنشاء سجل حركة المالك
            $transaction = OwnerTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'transaction_type' => OwnerTransaction::TYPE_CASH_DEPOSIT,
                'amount' => $amount,
                'treasury_id' => $treasuryId,
                'description' => $description,
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 2. إنشاء حركة في الخزينة (وارد)
            TreasuryTransaction::create([
                'company_id' => $companyId,
                'treasury_id' => $treasuryId,
                'type' => 'credit',
                'amount' => $amount,
                'reference_type' => OwnerTransaction::class,
                'reference_id' => $transaction->id,
                'description' => "إيداع من المالك - {$description}",
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 3. إنشاء قيد يومية
            self::createJournalEntry(
                $companyId,
                $branchId,
                'OD',
                'إيداعات المالك',
                $transactionDate ?? now()->toDateString(),
                OwnerTransaction::class,
                $transaction->id,
                "إيداع نقدي من المالك - {$description}",
                $amount,
                '1001', // الصندوق (مدين)
                '3002', // حساب جاري المالك (دائن)
                "إيداع نقدي - {$description}",
                "إيراد من المالك - {$description}",
                $createdBy
            );

            return $transaction;
        });
    }

    /**
     * سحب أموال من الخزينة للمالك
     */
    public static function withdrawCash(
        int $companyId,
        int $branchId,
        int $treasuryId,
        float $amount,
        string $description,
        ?int $createdBy = null,
        ?string $transactionDate = null
    ): OwnerTransaction {
        return DB::transaction(function () use ($companyId, $branchId, $treasuryId, $amount, $description, $createdBy, $transactionDate) {
            // 1. إنشاء سجل حركة المالك
            $transaction = OwnerTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'transaction_type' => OwnerTransaction::TYPE_CASH_WITHDRAWAL,
                'amount' => $amount,
                'treasury_id' => $treasuryId,
                'description' => $description,
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 2. إنشاء حركة في الخزينة (صادر)
            TreasuryTransaction::create([
                'company_id' => $companyId,
                'treasury_id' => $treasuryId,
                'type' => 'debit',
                'amount' => $amount,
                'reference_type' => OwnerTransaction::class,
                'reference_id' => $transaction->id,
                'description' => "سحب من المالك - {$description}",
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 3. إنشاء قيد يومية
            self::createJournalEntry(
                $companyId,
                $branchId,
                'OW',
                'سحوبات المالك',
                $transactionDate ?? now()->toDateString(),
                OwnerTransaction::class,
                $transaction->id,
                "سحب نقدي من المالك - {$description}",
                $amount,
                '3003', // سحوبات المالك (مدين)
                '1001', // الصندوق (دائن)
                "سحب نقدي - {$description}",
                "دفع للمالك - {$description}",
                $createdBy
            );

            return $transaction;
        });
    }

    /**
     * إرسال بضاعة من محل للمخزن
     */
    public static function dispatchGoods(
        int $companyId,
        int $branchId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $itemId,
        float $quantity,
        float $unitCost,
        string $description,
        ?int $createdBy = null,
        ?string $transactionDate = null
    ): OwnerTransaction {
        return DB::transaction(function () use ($companyId, $branchId, $fromWarehouseId, $toWarehouseId, $itemId, $quantity, $unitCost, $description, $createdBy, $transactionDate) {
            $totalCost = $quantity * $unitCost;

            // 1. إنشاء سجل حركة المالك
            $transaction = OwnerTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'transaction_type' => OwnerTransaction::TYPE_GOODS_DISPATCH,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'warehouse_id' => $fromWarehouseId,
                'description' => $description,
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 2. تحويل المخزون بين المخازن
            \App\Services\StockService::transferWarehouseStock(
                $fromWarehouseId,
                $toWarehouseId,
                $itemId,
                $quantity,
                $unitCost,
                $companyId,
                $branchId,
                $createdBy,
                "إرسال بضاعة من المالك - {$description}"
            );

            // 3. إنشاء قيد يومية
            $item = Item::find($itemId);
            self::createJournalEntry(
                $companyId,
                $branchId,
                'GD',
                'إرسال بضاعة من المالك',
                $transactionDate ?? now()->toDateString(),
                OwnerTransaction::class,
                $transaction->id,
                "إرسال بضاعة ({$item?->name_ar}) - {$description}",
                $totalCost,
                '3004', // أصول مملوكة للمالك (مدين)
                '1004', // المخزون (دائن)
                "بضاعة صادرة - {$description}",
                "بضاعة واردة - {$description}",
                $createdBy
            );

            return $transaction;
        });
    }

    /**
     * سحب بضاعة من المخزن لمحل
     */
    public static function receiveGoods(
        int $companyId,
        int $branchId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $itemId,
        float $quantity,
        float $unitCost,
        string $description,
        ?int $createdBy = null,
        ?string $transactionDate = null
    ): OwnerTransaction {
        return DB::transaction(function () use ($companyId, $branchId, $fromWarehouseId, $toWarehouseId, $itemId, $quantity, $unitCost, $description, $createdBy, $transactionDate) {
            $totalCost = $quantity * $unitCost;

            // 1. إنشاء سجل حركة المالك
            $transaction = OwnerTransaction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'transaction_type' => OwnerTransaction::TYPE_GOODS_RECEIVE,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'warehouse_id' => $toWarehouseId,
                'description' => $description,
                'transaction_date' => $transactionDate ?? now()->toDateString(),
                'created_by' => $createdBy,
            ]);

            // 2. تحويل المخزون بين المخازن
            \App\Services\StockService::transferWarehouseStock(
                $fromWarehouseId,
                $toWarehouseId,
                $itemId,
                $quantity,
                $unitCost,
                $companyId,
                $branchId,
                $createdBy,
                "سحب بضاعة للمالك - {$description}"
            );

            // 3. إنشاء قيد يومية
            $item = Item::find($itemId);
            self::createJournalEntry(
                $companyId,
                $branchId,
                'GR',
                'سحب بضاعة للمالك',
                $transactionDate ?? now()->toDateString(),
                OwnerTransaction::class,
                $transaction->id,
                "سحب بضاعة ({$item?->name_ar}) - {$description}",
                $totalCost,
                '1004', // المخزون (مدين)
                '3004', // أصول مملوكة للمالك (دائن)
                "بضاعة واردة - {$description}",
                "بضاعة صادرة - {$description}",
                $createdBy
            );

            return $transaction;
        });
    }

    /**
     * الحصول على كشف حساب المالك
     */
    public static function getStatement(
        int $companyId,
        ?int $branchId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = OwnerTransaction::where('company_id', $companyId)
            ->with(['branch', 'treasury', 'warehouse', 'item', 'createdBy']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * الحصول على رصيد المالك الإجمالي
     */
    public static function getBalance(int $companyId): array
    {
        // رأس المال
        $capitalAccount = self::findAccount($companyId, '3001');
        $capital = $capitalAccount ? (float) $capitalAccount->current_balance : 0;

        // حساب جاري المالك
        $currentAccount = self::findAccount($companyId, '3002');
        $currentBalance = $currentAccount ? (float) $currentAccount->current_balance : 0;

        // سحوبات المالك
        $drawingsAccount = self::findAccount($companyId, '3003');
        $drawings = $drawingsAccount ? (float) $drawingsAccount->current_balance : 0;

        // أصول مملوكة للمالك
        $assetsAccount = self::findAccount($companyId, '3004');
        $ownerAssets = $assetsAccount ? (float) $assetsAccount->current_balance : 0;

        // إجمالي حركات المالك النقدية
        $totalCashDeposits = OwnerTransaction::where('company_id', $companyId)
            ->where('transaction_type', OwnerTransaction::TYPE_CASH_DEPOSIT)
            ->sum('amount');

        $totalCashWithdrawals = OwnerTransaction::where('company_id', $companyId)
            ->where('transaction_type', OwnerTransaction::TYPE_CASH_WITHDRAWAL)
            ->sum('amount');

        // إجمالي حركات البضاعة
        $totalGoodsDispatched = OwnerTransaction::where('company_id', $companyId)
            ->where('transaction_type', OwnerTransaction::TYPE_GOODS_DISPATCH)
            ->sum('total_cost');

        $totalGoodsReceived = OwnerTransaction::where('company_id', $companyId)
            ->where('transaction_type', OwnerTransaction::TYPE_GOODS_RECEIVE)
            ->sum('total_cost');

        return [
            'capital' => $capital,
            'current_account_balance' => $currentBalance,
            'drawings' => $drawings,
            'owner_assets' => $ownerAssets,
            'net_owner_equity' => $capital + $currentBalance - $drawings + $ownerAssets,
            'total_cash_deposits' => $totalCashDeposits,
            'total_cash_withdrawals' => $totalCashWithdrawals,
            'net_cash_flow' => $totalCashDeposits - $totalCashWithdrawals,
            'total_goods_dispatched' => $totalGoodsDispatched,
            'total_goods_received' => $totalGoodsReceived,
            'net_goods_flow' => $totalGoodsReceived - $totalGoodsDispatched,
        ];
    }

    /**
     * البحث عن حساب بالشركة والرمز
     */
    private static function findAccount(int $companyId, string $code): ?Account
    {
        return Account::where('company_id', $companyId)
            ->where('account_code', $code . '-' . $companyId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * إنشاء نوع قيد يومية
     */
    private static function getOrCreateType(string $code, string $name): JournalEntryType
    {
        return JournalEntryType::firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'is_system' => true]
        );
    }

    /**
     * إنشاء قيد يومية
     */
    private static function createJournalEntry(
        int $companyId,
        int $branchId,
        string $typeCode,
        string $typeName,
        string $entryDate,
        string $referenceType,
        int $referenceId,
        string $description,
        float $amount,
        string $debitAccountCode,
        string $creditAccountCode,
        string $debitDescription,
        string $creditDescription,
        ?int $createdBy
    ): ?JournalEntry {
        $debitAccount = self::findAccount($companyId, $debitAccountCode);
        $creditAccount = self::findAccount($companyId, $creditAccountCode);

        if (!$debitAccount || !$creditAccount) {
            return null;
        }

        $type = self::getOrCreateType($typeCode, $typeName);

        $entry = JournalEntry::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'journal_entry_type_id' => $type->id,
            'entry_date' => $entryDate,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'total_debit' => $amount,
            'total_credit' => $amount,
            'status' => 'posted',
            'created_by' => $createdBy,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccount->id,
            'description' => $debitDescription,
            'debit' => $amount,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => $creditDescription,
            'debit' => 0,
            'credit' => $amount,
        ]);

        // تحديث أرصدة الحسابات
        $debitAccount->update([
            'current_balance' => DB::raw("COALESCE(current_balance, 0) + {$amount}"),
        ]);

        $creditAccount->update([
            'current_balance' => DB::raw("COALESCE(current_balance, 0) - {$amount}"),
        ]);

        return $entry;
    }
}
