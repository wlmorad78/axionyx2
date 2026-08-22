<?php

namespace Tests\Feature;

use App\Models\Company\Company;
use App\Models\Customer;
use App\Models\HR\Employee;
use App\Models\Item;
use App\Models\Sales\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HandheldSyncInvoicesClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Queue::fake();
        Event::fake();

        // The global CompanyAccess/CompanyScope middleware query the `roles`
        // table, whose sqlite test schema is missing `deleted_at` (a separate
        // pre-existing schema gap). The handler scopes everything by
        // company_id itself, so we skip these middleware for the test.
        $this->withoutMiddleware([
            \App\Http\Middleware\CompanyAccessMiddleware::class,
            \App\Http\Middleware\CompanyScope::class,
        ]);

        // RefreshDatabase rollback can be defeated by the route's nested
        // DB::transaction under sqlite :memory:, so we explicitly isolate.
        \DB::statement('PRAGMA foreign_keys = OFF');
        \DB::table('sales_invoice_items')->delete();
        \DB::table('sales_invoices')->delete();
        \DB::table('number_series')->delete();
        \DB::statement('PRAGMA foreign_keys = ON');
    }

    private function makeUserWithEmployee(): array
    {
        $company = Company::create([
            'code' => 'TST',
            'name_ar' => 'Test Company',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'usercode' => 'USR' . uniqid(),
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'employee_code' => 'EMP' . uniqid(),
            'first_name_ar' => 'تست',
            'last_name_ar' => 'موظف',
            'first_name_en' => 'Test',
            'email' => 'tester@example.com',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'code' => 'CUST' . uniqid(),
            'name_ar' => 'Test Customer',
            'name_en' => 'Test Customer',
        ]);

        $item = Item::create([
            'company_id' => $company->id,
            'code' => 'ITEM' . uniqid(),
            'name_ar' => 'Test Item',
            'is_active' => true,
        ]);

        $branch = \App\Models\Company\Branch::create([
            'company_id' => $company->id,
            'code' => 'BR' . uniqid(),
            'name' => 'Main Branch',
        ]);

        Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'code' => 'WH' . uniqid(),
            'name' => 'WH',
            'is_active' => true,
        ]);

        return [$user, $customer, $item];
    }

    private function payload(string $clientUuid, string $action, int $customerId, string $itemCode, int $qty, int $price, string $localNo = 'LOCAL-001'): array
    {
        return [
            'invoices' => [[
                'client_uuid' => $clientUuid,
                'action' => $action,
                'customer_id' => $customerId,
                'invoice_date' => '2025-08-16',
                'invoice_no' => $localNo,
                'paid_amount' => $qty * $price,
                'items' => [[
                    'item_code' => $itemCode,
                    'qty' => $qty,
                    'price' => $price,
                    'tax_percent' => 0,
                ]],
            ]],
        ];
    }

    private function sync($user, array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user, 'sanctum')->postJson('/api/handheld/sync-invoices', array_merge(
            $payload,
            ['_salesman_id' => $user->id]
        ));
    }

    private function countByUuid(string $clientUuid): int
    {
        return SalesInvoice::withTrashed()->where('client_uuid', $clientUuid)->count();
    }

    public function test_create_then_update_same_client_uuid_is_idempotent(): void
    {
        [$user, $customer, $item] = $this->makeUserWithEmployee();
        $uuid = 'U' . uniqid();

        // 1) First sync (treated as create)
        $response = $this->sync($user, $this->payload($uuid, 'create', $customer->id, $item->code, 2, 50));
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.status', 'synced');

        $invoice = SalesInvoice::withTrashed()->where('client_uuid', $uuid)->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->invoice_no);
        $this->assertEquals('LOCAL-001', $invoice->invoice_no, 'invoice_no should come from Flutter');
        $firstInvoiceNo = $invoice->invoice_no;
        $this->assertEquals(100, $invoice->net_total);

        // 2) Re-sync SAME client_uuid with action=create (sync_manager forces create)
        $response2 = $this->sync($user, $this->payload($uuid, 'create', $customer->id, $item->code, 3, 50));
        $response2->assertJsonPath('data.0.status', 'updated');

        // No duplicate row, invoice_no stays stable, totals updated
        $this->assertEquals(1, $this->countByUuid($uuid));
        $invoice2 = SalesInvoice::withTrashed()->where('client_uuid', $uuid)->first();
        $this->assertEquals($firstInvoiceNo, $invoice2->invoice_no);
        $this->assertEquals(150, $invoice2->net_total);
    }

    public function test_different_client_uuid_creates_separate_invoice(): void
    {
        [$user, $customer, $item] = $this->makeUserWithEmployee();
        $a = 'U' . uniqid();
        $b = 'U' . uniqid();

        $this->sync($user, $this->payload($a, 'create', $customer->id, $item->code, 1, 50, 'INV-001'));
        $this->sync($user, $this->payload($b, 'create', $customer->id, $item->code, 1, 50, 'INV-002'));

        $this->assertEquals(1, $this->countByUuid($a));
        $this->assertEquals(1, $this->countByUuid($b));
    }

    public function test_delete_then_recreate_same_client_uuid_restores(): void
    {
        [$user, $customer, $item] = $this->makeUserWithEmployee();
        $d = 'U' . uniqid();

        $this->sync($user, $this->payload($d, 'create', $customer->id, $item->code, 1, 50))
            ->assertJsonPath('data.0.status', 'synced');

        $this->sync($user, $this->payload($d, 'delete', $customer->id, $item->code, 1, 50))
            ->assertJsonPath('data.0.status', 'deleted');

        $raw = \DB::table('sales_invoices')->where('client_uuid', $d)->first();
        $this->assertNotNull($raw, 'row should exist');
        $this->assertNotNull($raw->deleted_at, 'should be soft-deleted');

        // Re-sync same client_uuid -> should restore (update), not duplicate
        $this->sync($user, $this->payload($d, 'create', $customer->id, $item->code, 2, 50))
            ->assertJsonPath('data.0.status', 'updated');

        $this->assertEquals(1, $this->countByUuid($d));
        $restored = SalesInvoice::withTrashed()->where('client_uuid', $d)->first();
        $this->assertNull($restored->deleted_at);
        $this->assertEquals(100, $restored->net_total);
    }
}
