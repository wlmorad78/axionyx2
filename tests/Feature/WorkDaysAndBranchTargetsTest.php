<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkDaysAndBranchTargetsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branchA;
    private Branch $branchB;
    private User $user;
    private string $token;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        CompanyContext::clear();

        $this->company = Company::create([
            'code' => 'WD-CO',
            'name_ar' => 'شركة أيام العمل',
            'name_en' => 'Work Days Co',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'company_id' => $this->company->id,
            'code' => 'WD-B1',
            'name' => 'فرع أ',
            'name_ar' => 'فرع أ',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'company_id' => $this->company->id,
            'code' => 'WD-B2',
            'name' => 'فرع ب',
            'name_ar' => 'فرع ب',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'usercode' => 9101,
            'name' => 'Work Days Tester',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => $this->company->id,
        ]);
        $this->user->companies()->attach($this->company->id);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'X-Company-Id' => (string) $this->company->id,
            'X-Branch-Id' => (string) $this->branchA->id,
        ];
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    // ── WORK DAYS ──

    public function test_work_days_index_requires_auth(): void
    {
        $this->getJson('/api/work-days')->assertStatus(401);
    }

    public function test_work_days_index_returns_list(): void
    {
        $this->withHeaders($this->headers)
            ->getJson('/api/work-days?per_page=200&company_id=' . $this->company->id)
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_work_days_store_and_update(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 10,
            'work_days' => [1, 3, 5, 10, 20],
            'is_active' => true,
        ];

        $create = $this->withHeaders($this->headers)
            ->postJson('/api/work-days', $payload);
        $create->assertStatus(201);
        $id = $create->json('id');

        $this->assertDatabaseHas('work_days', [
            'id' => $id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 10,
        ]);

        $update = $this->withHeaders($this->headers)
            ->putJson('/api/work-days/' . $id, array_merge($payload, [
                'work_days' => [2, 4, 6],
                'is_active' => false,
            ]));
        $update->assertStatus(200);

        $this->assertDatabaseHas('work_days', [
            'id' => $id,
            'is_active' => false,
        ]);
    }

    public function test_work_days_store_uses_body_branch_id_not_header(): void
    {
        // header = branchA, body = branchB → يجب حفظ فرع body
        $this->withHeaders($this->headers)
            ->postJson('/api/work-days', [
                'company_id' => $this->company->id,
                'branch_id' => $this->branchB->id,
                'year' => 2026,
                'month' => 11,
                'work_days' => [1, 2],
                'is_active' => true,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('work_days', [
            'branch_id' => $this->branchB->id,
            'year' => 2026,
            'month' => 11,
        ]);
    }

    public function test_work_days_store_is_idempotent_per_branch_year_month(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 12,
            'work_days' => [1, 2],
            'is_active' => true,
        ];

        $this->withHeaders($this->headers)->postJson('/api/work-days', $payload)->assertStatus(201);
        $this->withHeaders($this->headers)->postJson('/api/work-days', $payload)->assertStatus(200);

        $this->assertSame(1, \App\Models\WorkDay::where([
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 12,
        ])->count());
    }

    public function test_work_days_store_validates_payload(): void
    {
        $this->withHeaders($this->headers)
            ->postJson('/api/work-days', ['work_days' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id', 'year', 'month', 'work_days']);
    }

    public function test_work_days_show_and_destroy(): void
    {
        $record = \App\Models\WorkDay::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2025,
            'month' => 1,
            'work_days' => [1, 2],
            'is_active' => true,
        ]);

        $this->withHeaders($this->headers)
            ->getJson('/api/work-days/' . $record->id)
            ->assertStatus(200)
            ->assertJson(['id' => $record->id]);

        $this->withHeaders($this->headers)
            ->deleteJson('/api/work-days/' . $record->id)
            ->assertStatus(204);

        $this->assertDatabaseMissing('work_days', ['id' => $record->id]);
    }

    // ── BRANCH TARGETS ──

    public function test_branch_targets_index_returns_list(): void
    {
        $this->withHeaders($this->headers)
            ->getJson('/api/branch-targets?per_page=500&company_id=' . $this->company->id)
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'total']);
    }

    public function test_branch_targets_crud(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 10,
            'target_amount' => 150000.50,
        ];

        $create = $this->withHeaders($this->headers)
            ->postJson('/api/branch-targets', $payload);
        $create->assertStatus(201);
        $id = $create->json('id');

        $this->assertDatabaseHas('branch_targets', [
            'id' => $id,
            'branch_id' => $this->branchA->id,
            'target_amount' => 150000.50,
        ]);

        $this->withHeaders($this->headers)
            ->putJson('/api/branch-targets/' . $id, array_merge($payload, [
                'target_amount' => 200000,
            ]))
            ->assertStatus(200);

        $this->assertDatabaseHas('branch_targets', [
            'id' => $id,
            'target_amount' => 200000,
        ]);

        $this->withHeaders($this->headers)
            ->deleteJson('/api/branch-targets/' . $id)
            ->assertStatus(204);

        $this->assertDatabaseMissing('branch_targets', ['id' => $id]);
    }

    public function test_branch_targets_store_uses_body_branch_id_not_header(): void
    {
        $this->withHeaders($this->headers)
            ->postJson('/api/branch-targets', [
                'company_id' => $this->company->id,
                'branch_id' => $this->branchB->id,
                'year' => 2026,
                'month' => 11,
                'target_amount' => 999,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('branch_targets', [
            'branch_id' => $this->branchB->id,
            'target_amount' => 999,
        ]);
    }

    public function test_branch_targets_store_validates_payload(): void
    {
        $this->withHeaders($this->headers)
            ->postJson('/api/branch-targets', ['target_amount' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branch_id', 'year', 'month', 'target_amount']);
    }

    public function test_work_days_are_filtered_by_company_id_query(): void
    {
        $otherCompany = Company::create([
            'code' => 'WD-OTHER',
            'name_ar' => 'شركة أخرى',
            'name_en' => 'Other Co',
            'is_active' => true,
        ]);
        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'code' => 'WD-OB',
            'name' => 'فرع آخر',
            'name_ar' => 'فرع آخر',
            'is_active' => true,
        ]);

        \App\Models\WorkDay::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branchA->id,
            'year' => 2026,
            'month' => 1,
            'work_days' => [1],
            'is_active' => true,
        ]);
        \App\Models\WorkDay::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'year' => 2026,
            'month' => 1,
            'work_days' => [2],
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->headers)
            ->getJson('/api/work-days?company_id=' . $this->company->id . '&per_page=200')
            ->assertStatus(200);

        $branchIds = collect($response->json('data'))->pluck('branch_id')->all();
        $this->assertContains($this->branchA->id, $branchIds);
        $this->assertNotContains($otherBranch->id, $branchIds);
    }
}
