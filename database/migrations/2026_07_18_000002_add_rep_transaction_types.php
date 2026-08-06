<?php
use App\Models\InventoryTransactionType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void {
        $types = [
            ['code' => 'TRANSFER_TO_REP', 'name' => 'تحميل لمندوب', 'effect' => 'neutral'],
            ['code' => 'TRANSFER_FROM_REP', 'name' => 'تفريغ من مندوب', 'effect' => 'neutral'],
            ['code' => 'REP_SALE', 'name' => 'بيع مندوب', 'effect' => 'subtraction'],
            ['code' => 'REP_RETURN', 'name' => 'مرتجع لمخزون المندوب', 'effect' => 'addition'],
        ];
        foreach ($types as $t) {
            InventoryTransactionType::updateOrCreate(['code' => $t['code']], $t);
        }
    }

    public function down(): void {
        InventoryTransactionType::whereIn('code', ['TRANSFER_TO_REP', 'TRANSFER_FROM_REP', 'REP_SALE', 'REP_RETURN'])->forceDelete();
    }
};
