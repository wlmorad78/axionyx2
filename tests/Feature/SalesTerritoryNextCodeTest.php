<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Sales\SalesTerritoryController;
use App\Models\SalesTerritory;
use App\Models\SalesTerritoryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesTerritoryNextCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_code_uses_highest_existing_code_not_latest_record_id(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'TST-CO',
            'name' => 'Test company',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $type = SalesTerritoryType::create([
            'code' => 'TST',
            'name_ar' => 'نوع اختبار',
        ]);

        foreach (['ST-0001', 'ST-0002', 'ST-0003'] as $index => $code) {
            SalesTerritory::create([
                'company_id' => $companyId,
                'sales_territory_type_id' => $type->id,
                'code' => $code,
                'name_ar' => 'منطقة ' . ($index + 1),
            ]);
        }

        SalesTerritory::create([
            'company_id' => $companyId,
            'sales_territory_type_id' => $type->id,
            'code' => 'AREA-0009',
            'name_ar' => 'منطقة بكود آخر',
        ]);

        $response = app(SalesTerritoryController::class)
            ->nextCode(Request::create('/api/sales-territories/next-code', 'GET'));

        $this->assertSame('ST-0004', $response->getData(true)['code']);
    }

    public function test_next_code_includes_soft_deleted_territories(): void
    {
        $companyId = DB::table('companies')->insertGetId([
            'code' => 'TST-CO-2',
            'name' => 'Test company',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $type = SalesTerritoryType::create([
            'code' => 'TST-2',
            'name_ar' => 'نوع اختبار',
        ]);
        $territory = SalesTerritory::create([
            'company_id' => $companyId,
            'sales_territory_type_id' => $type->id,
            'code' => 'ST-0003',
            'name_ar' => 'منطقة محذوفة',
        ]);
        $territory->delete();

        $response = app(SalesTerritoryController::class)
            ->nextCode(Request::create('/api/sales-territories/next-code', 'GET'));

        $this->assertSame('ST-0004', $response->getData(true)['code']);
    }
}
