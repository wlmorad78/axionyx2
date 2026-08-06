<?php

namespace Database\Seeders;

use App\Models\InventoryTransactionType;
use App\Models\JournalEntryType;
use App\Models\AccountType;
use App\Models\MasterDataType;
use App\Models\VehicleType;
use App\Models\OpportunityStage;
use App\Models\MarketingSupportType;
use App\Models\CustomerAgreementType;
use App\Models\KpiDefinition;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // --- Inventory Transaction Types ---
        $invTypes = [
            ['code' => 'OPENING_BALANCE', 'name' => 'رصيد افتتاحي', 'effect' => 'addition'],
            ['code' => 'PURCHASE_RECEIPT', 'name' => 'استلام مشتريات', 'effect' => 'addition'],
            ['code' => 'SALES_RETURN', 'name' => 'مرتجع مبيعات', 'effect' => 'addition'],
            ['code' => 'STOCK_ADJUSTMENT_ADD', 'name' => 'تسوية مخزون (زيادة)', 'effect' => 'addition'],
            ['code' => 'WAREHOUSE_TRANSFER_IN', 'name' => 'تحويل مخزني (وارد)', 'effect' => 'addition'],
            ['code' => 'ISSUE_ORDER', 'name' => 'أمر صرف', 'effect' => 'subtraction'],
            ['code' => 'SALES_INVOICE', 'name' => 'فاتورة مبيعات', 'effect' => 'subtraction'],
            ['code' => 'PURCHASE_RETURN', 'name' => 'مرتجع مشتريات', 'effect' => 'subtraction'],
            ['code' => 'STOCK_ADJUSTMENT_SUB', 'name' => 'تسوية مخزون (نقص)', 'effect' => 'subtraction'],
            ['code' => 'WAREHOUSE_TRANSFER_OUT', 'name' => 'تحويل مخزني (صادر)', 'effect' => 'subtraction'],
            ['code' => 'DAMAGE', 'name' => 'تلف', 'effect' => 'subtraction'],
            ['code' => 'EXPIRY', 'name' => 'انتهاء صلاحية', 'effect' => 'subtraction'],
            ['code' => 'REVALUATION', 'name' => 'إعادة تقييم', 'effect' => 'neutral'],
        ];
        foreach ($invTypes as $t) {
            InventoryTransactionType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- Journal Entry Types ---
        $jeTypes = [
            ['code' => 'OPENING', 'name' => 'قيد افتتاحي', 'is_system' => true],
            ['code' => 'SALES', 'name' => 'قيد مبيعات', 'is_system' => true],
            ['code' => 'PURCHASE', 'name' => 'قيد مشتريات', 'is_system' => true],
            ['code' => 'COLLECTION', 'name' => 'قيد تحصيل', 'is_system' => true],
            ['code' => 'PAYMENT', 'name' => 'قيد دفع', 'is_system' => true],
            ['code' => 'RETURN', 'name' => 'قيد مرتجع', 'is_system' => true],
            ['code' => 'JOURNAL', 'name' => 'قيد يومية عام', 'is_system' => false],
            ['code' => 'DEPRECIATION', 'name' => 'قيد إهلاك', 'is_system' => true],
            ['code' => 'BANK_TRANSFER', 'name' => 'تحويل بنكي', 'is_system' => true],
            ['code' => 'RECEIPT_VOUCHER', 'name' => 'سند قبض', 'is_system' => true],
            ['code' => 'PAYMENT_VOUCHER', 'name' => 'سند صرف', 'is_system' => true],
        ];
        foreach ($jeTypes as $t) {
            JournalEntryType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- Account Types ---
        $accTypes = [
            ['code' => 'ASSET', 'name' => 'أصول', 'nature' => 'asset'],
            ['code' => 'LIABILITY', 'name' => 'خصوم', 'nature' => 'liability'],
            ['code' => 'EQUITY', 'name' => 'حقوق الملكية', 'nature' => 'equity'],
            ['code' => 'REVENUE', 'name' => 'إيرادات', 'nature' => 'revenue'],
            ['code' => 'EXPENSE', 'name' => 'مصروفات', 'nature' => 'expense'],
            ['code' => 'COST_OF_GOODS', 'name' => 'تكلفة المبيعات', 'nature' => 'expense'],
        ];
        foreach ($accTypes as $t) {
            AccountType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- Master Data Request Types ---
        $mdTypes = [
            ['code' => 'CUSTOMER', 'name' => 'عميل', 'entity_name' => 'Customer'],
            ['code' => 'SUPPLIER', 'name' => 'مورد', 'entity_name' => 'Supplier'],
            ['code' => 'ITEM', 'name' => 'صنف', 'entity_name' => 'Item'],
            ['code' => 'PRICE', 'name' => 'سعر', 'entity_name' => 'ItemPrice'],
            ['code' => 'INCENTIVE', 'name' => 'تحفيز', 'entity_name' => 'SalesIncentive'],
            ['code' => 'EMPLOYEE', 'name' => 'موظف', 'entity_name' => 'Employee'],
            ['code' => 'AGREEMENT', 'name' => 'اتفاقية', 'entity_name' => 'CustomerAgreement'],
            ['code' => 'MARKETING_ASSET', 'name' => 'أصل تسويقي', 'entity_name' => 'MarketingAsset'],
        ];
        foreach ($mdTypes as $t) {
            MasterDataType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- Vehicle Types ---
        $vTypes = [
            ['name' => 'شاحنة صغيرة', 'description' => 'Small truck - less than 3 tons'],
            ['name' => 'شاحنة متوسطة', 'description' => 'Medium truck - 3 to 7 tons'],
            ['name' => 'شاحنة كبيرة', 'description' => 'Large truck - more than 7 tons'],
            ['name' => 'شاحنة نصف مقطورة', 'description' => 'Semi-trailer truck'],
            ['name' => 'فان', 'description' => 'Van'],
            ['name' => 'باص', 'description' => 'Bus'],
            ['name' => 'دراجة نارية', 'description' => 'Motorcycle'],
        ];
        foreach ($vTypes as $t) {
            VehicleType::updateOrCreate(['name' => $t['name']], $t);
        }

        // --- Opportunity Stages ---
        $oStages = [
            ['name' => 'جديد', 'sequence' => 1, 'probability' => 10],
            ['name' => 'تم التواصل', 'sequence' => 2, 'probability' => 25],
            ['name' => 'تم التقييم', 'sequence' => 3, 'probability' => 50],
            ['name' => 'في التفاوض', 'sequence' => 4, 'probability' => 75],
            ['name' => 'قيد المراجعة', 'sequence' => 5, 'probability' => 85],
            ['name' => 'فاز بالصفقة', 'sequence' => 6, 'probability' => 100],
            ['name' => 'خسر', 'sequence' => 7, 'probability' => 0],
            ['name' => 'مؤجّل', 'sequence' => 8, 'probability' => 30],
        ];
        foreach ($oStages as $s) {
            OpportunityStage::updateOrCreate(['name' => $s['name']], $s);
        }

        // --- Marketing Support Types ---
        $mktSupportTypes = [
            ['code' => 'FRIDGE', 'name' => 'ثلاجة', 'is_active' => true],
            ['code' => 'SHELF', 'name' => 'رف', 'is_active' => true],
            ['code' => 'BANNER', 'name' => 'لافتة', 'is_active' => true],
            ['code' => 'STAND', 'name' => 'ستاند', 'is_active' => true],
            ['code' => 'DISPLAY', 'name' => 'عرض', 'is_active' => true],
            ['code' => 'SIGN', 'name' => 'لوحة', 'is_active' => true],
            ['code' => 'COOLER', 'name' => 'مبرد', 'is_active' => true],
        ];
        foreach ($mktSupportTypes as $t) {
            MarketingSupportType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- Customer Agreement Types ---
        $agreementTypes = [
            ['code' => 'DISCOUNT', 'name' => 'خصم', 'is_active' => true],
            ['code' => 'MARKETING_SUPPORT', 'name' => 'دعم تسويقي', 'is_active' => true],
            ['code' => 'DISPLAY_SUPPORT', 'name' => 'دعم عرض', 'is_active' => true],
            ['code' => 'ANNUAL_BONUS', 'name' => 'مكافأة سنوية', 'is_active' => true],
            ['code' => 'REBATE', 'name' => 'خصم استردادي', 'is_active' => true],
            ['code' => 'EXCLUSIVE_DEAL', 'name' => 'صفقة حصرية', 'is_active' => true],
        ];
        foreach ($agreementTypes as $t) {
            CustomerAgreementType::updateOrCreate(['code' => $t['code']], $t);
        }

        // --- KPI Definitions ---
        $kpis = [
            ['kpi_code' => 'SALES_TARGET_ACHIEVEMENT', 'kpi_name' => 'تحقيق هدف المبيعات', 'module' => 'sales', 'target_type' => 'percentage'],
            ['kpi_code' => 'COLLECTION_RATE', 'kpi_name' => 'نسبة التحصيل', 'module' => 'finance', 'target_type' => 'percentage'],
            ['kpi_code' => 'VISIT_COVERAGE', 'kpi_name' => 'تغطية الزيارات', 'module' => 'distribution', 'target_type' => 'percentage'],
            ['kpi_code' => 'NEW_CUSTOMERS', 'kpi_name' => 'العملاء الجدد', 'module' => 'sales', 'target_type' => 'count'],
            ['kpi_code' => 'RETURN_RATE', 'kpi_name' => 'نسبة المرتجعات', 'module' => 'sales', 'target_type' => 'percentage'],
            ['kpi_code' => 'STOCK_TURNOVER', 'kpi_name' => 'دوران المخزون', 'module' => 'inventory', 'target_type' => 'ratio'],
            ['kpi_code' => 'SHRINKAGE', 'kpi_name' => 'النقص في المخزون', 'module' => 'inventory', 'target_type' => 'percentage'],
            ['kpi_code' => 'EMPLOYEE_ATTENDANCE', 'kpi_name' => 'الحضور والانضباط', 'module' => 'hr', 'target_type' => 'percentage'],
            ['kpi_code' => 'MERCHANDISING_SCORE', 'kpi_name' => 'نتيجة التسوّق', 'module' => 'merchandising', 'target_type' => 'score'],
            ['kpi_code' => 'SURVEY_COMPLETION', 'kpi_name' => 'إكمال الاستبيانات', 'module' => 'survey', 'target_type' => 'percentage'],
        ];
        foreach ($kpis as $k) {
            KpiDefinition::updateOrCreate(['kpi_code' => $k['kpi_code']], $k);
        }
    }
}
