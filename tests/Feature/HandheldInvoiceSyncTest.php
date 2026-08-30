<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HandheldInvoiceSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompany(string $code): Company
    {
        return Company::create([
            'code' => $code,
            'name_ar' => 'Company ' . $code,
            'is_active' => true,
        ]);
    }

    private function makeUser(Company $company, string $usercode): User
    {
        return User::create([
            'usercode' => $usercode,
            'name' => 'User ' . $usercode,
            'email' => $usercode . '@example.com',
            'password' => bcrypt('secret'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
    }

    private function makeEmployee(Company $company, User $user): Employee
    {
        return Employee::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'employee_code' => 'EMP-' . $user->usercode,
            'first_name_ar' => 'Emp',
            'last_name_ar' => 'Loyee',
            'mobile' => '0000000000',
            'is_active' => true,
        ]);
    }

    private function makeCustomer(Company $company, string $code): Customer
    {
        return Customer::create([
            'company_id' => $company->id,
            'code' => $code,
            'name_ar' => 'Customer ' . $code,
            'is_active' => true,
        ]);
    }

    private function makeItem(Company $company, string $code): Item
    {
        return Item::create([
            'company_id' => $company->id,
            'code' => $code,
            'name_ar' => 'Item ' . $code,
            'is_active' => true,
            'base_unit_id' => null,
        ]);
    }

    private function invoiceRecord(string $clientUuid, string $action, array $overrides = []): array
    {
        $item = $overrides['_item_code'] ?? 'ITEM-TEST';

        $payload = array_merge([
            'client_uuid' => $clientUuid,
            'customer_id' => $overrides['customer_id'] ?? null,
            'invoice_no' => $overrides['invoice_no'] ?? ('TEMP-' . $clientUuid),
            'temp_invoice_no' => $overrides['temp_invoice_no'] ?? null,
            'net_total' => $overrides['net_total'] ?? 100,
            'paid_amount' => $overrides['paid_amount'] ?? 100,
            'items' => $overrides['items'] ?? [
                [
                    'item_code' => $item,
                    'quantity' => 2,
                    'unit_price' => 50,
                    'tax_percent' => 0,
                ],
            ],
        ], $overrides['extra_payload'] ?? []);

        return [
            'client_uuid' => $clientUuid,
            'uuid' => $clientUuid, // legacy Flutter field
            'entity_type' => 'sale',
            'action' => $action,
            'payload' => $payload,
        ];
    }

    private function push(User $user, array $records): array
    {
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/handheld2/sync/push', ['records' => $records]);

        return $response->json('results');
    }

    public function test_create_invoice_first_time(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $employee = $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $item = $this->makeItem($company, 'ITEM-TEST');

        $record = $this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer->id]);
        $results = $this->push($user, [$record]);

        $this->assertCount(1, $results);
        $this->assertEquals('synced', $results[0]['status']);
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('SALE-1', $results[0]['client_uuid']);
        $this->assertNotEmpty($results[0]['invoice_no']);
        $this->assertNotEquals('TEMP-SALE-1', $results[0]['invoice_no']);

        $invoice = SalesInvoice::where('client_uuid', 'SALE-1')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($company->id, $invoice->company_id);
        $this->assertEquals(100, $invoice->net_total);
        $this->assertCount(1, $invoice->items);
    }

    public function test_create_same_client_uuid_again_is_idempotent(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $this->makeItem($company, 'ITEM-TEST');

        $record = $this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer->id]);
        $this->push($user, [$record]);
        $results = $this->push($user, [$record]);

        $this->assertEquals('already_synced', $results[0]['status']);
        $this->assertCount(1, SalesInvoice::where('client_uuid', 'SALE-1')->get());
    }

    public function test_update_existing_invoice(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $this->makeItem($company, 'ITEM-TEST');

        $this->push($user, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer->id])]);

        $updated = $this->invoiceRecord('SALE-1', 'update', [
            'customer_id' => $customer->id,
            'net_total' => 300,
            'paid_amount' => 300,
            'items' => [
                ['item_code' => 'ITEM-TEST', 'quantity' => 3, 'unit_price' => 100, 'tax_percent' => 0],
            ],
        ]);
        $results = $this->push($user, [$updated]);

        $this->assertEquals('synced', $results[0]['status']);
        $this->assertCount(1, SalesInvoice::where('client_uuid', 'SALE-1')->get());

        $invoice = SalesInvoice::where('client_uuid', 'SALE-1')->first();
        $this->assertEquals(300, $invoice->net_total);
        $this->assertEquals(3, $invoice->items->sum('qty'));
    }

    public function test_delete_existing_invoice(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $this->makeItem($company, 'ITEM-TEST');

        $this->push($user, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer->id])]);

        $results = $this->push($user, [$this->invoiceRecord('SALE-1', 'delete')]);

        $this->assertEquals('synced', $results[0]['status']);
        $invoice = SalesInvoice::where('client_uuid', 'SALE-1')->first();
        $this->assertEquals('cancelled', $invoice->status);
    }

    public function test_delete_same_invoice_again_is_idempotent(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $this->makeItem($company, 'ITEM-TEST');

        $this->push($user, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer->id])]);
        $this->push($user, [$this->invoiceRecord('SALE-1', 'delete')]);
        $results = $this->push($user, [$this->invoiceRecord('SALE-1', 'delete')]);

        $this->assertEquals('already_deleted', $results[0]['status']);
        $this->assertTrue($results[0]['success']);
    }

    public function test_different_companies_can_use_same_client_uuid(): void
    {
        $company1 = $this->makeCompany('C1');
        $user1 = $this->makeUser($company1, 'U1');
        $this->makeEmployee($company1, $user1);
        $customer1 = $this->makeCustomer($company1, 'CU1');
        $this->makeItem($company1, 'ITEM-TEST');

        $company2 = $this->makeCompany('C2');
        $user2 = $this->makeUser($company2, 'U2');
        $this->makeEmployee($company2, $user2);
        $customer2 = $this->makeCustomer($company2, 'CU2');
        $this->makeItem($company2, 'ITEM-TEST');

        $this->push($user1, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer1->id])]);
        $this->push($user2, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer2->id])]);

        $this->assertCount(2, SalesInvoice::where('client_uuid', 'SALE-1')->get());
        $this->assertEquals(
            1,
            SalesInvoice::where('client_uuid', 'SALE-1')->where('company_id', $company1->id)->count()
        );
        $this->assertEquals(
            1,
            SalesInvoice::where('client_uuid', 'SALE-1')->where('company_id', $company2->id)->count()
        );
    }

    public function test_duplicate_invoice_no_is_never_generated(): void
    {
        $company = $this->makeCompany('C1');
        $user = $this->makeUser($company, 'U1');
        $this->makeEmployee($company, $user);
        $customer = $this->makeCustomer($company, 'CU1');
        $this->makeItem($company, 'ITEM-TEST');

        $records = [];
        for ($i = 1; $i <= 25; $i++) {
            $records[] = $this->invoiceRecord('SALE-' . $i, 'create', ['customer_id' => $customer->id]);
        }
        $this->push($user, $records);

        $invoices = SalesInvoice::where('company_id', $company->id)->get();
        $this->assertCount(25, $invoices);
        $this->assertEquals(25, $invoices->pluck('invoice_no')->unique()->count());
    }

    public function test_update_cannot_touch_invoice_from_another_company(): void
    {
        $company1 = $this->makeCompany('C1');
        $user1 = $this->makeUser($company1, 'U1');
        $this->makeEmployee($company1, $user1);
        $customer1 = $this->makeCustomer($company1, 'CU1');
        $this->makeItem($company1, 'ITEM-TEST');

        $company2 = $this->makeCompany('C2');
        $user2 = $this->makeUser($company2, 'U2');
        $this->makeEmployee($company2, $user2);
        $customer2 = $this->makeCustomer($company2, 'CU2');
        $this->makeItem($company2, 'ITEM-TEST');

        // company2 owns SALE-1
        $this->push($user2, [$this->invoiceRecord('SALE-1', 'create', ['customer_id' => $customer2->id])]);
        $original = SalesInvoice::where('client_uuid', 'SALE-1')->where('company_id', $company2->id)->first();
        $originalNet = $original->net_total;

        // company1 tries to update company2's invoice
        $results = $this->push($user1, [$this->invoiceRecord('SALE-1', 'update', [
            'customer_id' => $customer1->id,
            'net_total' => 999,
            'items' => [['item_code' => 'ITEM-TEST', 'quantity' => 9, 'unit_price' => 111, 'tax_percent' => 0]],
        ])]);

        $this->assertEquals('not_found', $results[0]['status']);

        $original->refresh();
        $this->assertEquals($originalNet, $original->net_total);
        $this->assertCount(1, SalesInvoice::where('client_uuid', 'SALE-1')->get());
    }
}
