<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Permissions\Role;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $regularUser;
    private User $superAdmin;
    private User $multiCompanyUser;

    protected function setUp(): void
    {
        parent::setUp();

        CompanyContext::clear();

        $this->companyA = Company::create([
            'code' => 'SEC-A',
            'name_ar' => 'شركة أ',
            'name_en' => 'Company A',
            'is_active' => true,
        ]);

        $this->companyB = Company::create([
            'code' => 'SEC-B',
            'name_ar' => 'شركة ب',
            'name_en' => 'Company B',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin'], [
            'code' => 'admin',
            'description' => 'Administrator',
        ]);

        // Regular user: belongs to Company A only
        $this->regularUser = User::create([
            'usercode' => 9001,
            'name' => 'Regular User',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => $this->companyA->id,
        ]);
        $this->regularUser->companies()->attach($this->companyA);

        // Super Admin: Admin role, no company_id
        $this->superAdmin = User::create([
            'usercode' => 9002,
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => null,
        ]);
        $this->superAdmin->roles()->attach($adminRole);

        // Multi-company user: belongs to both A and B
        $this->multiCompanyUser = User::create([
            'usercode' => 9003,
            'name' => 'Multi Company User',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => $this->companyA->id,
        ]);
        $this->multiCompanyUser->companies()->attach([$this->companyA->id, $this->companyB->id]);
    }

    protected function tearDown(): void
    {
        CompanyContext::clear();
        parent::tearDown();
    }

    private function authRequest(User $user, string $method, string $uri, array $headers = [], array $data = []): \Illuminate\Testing\TestResponse
    {
        $token = $user->createToken('test-token')->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;

        return $this->withHeaders($headers)->json($method, $uri, $data);
    }

    // ── 1. Authorized user + authorized company → 200 ──

    public function test_authorized_user_with_own_company_returns_200(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyA->id]
        );

        $response->assertStatus(200);
        $response->assertJson(['id' => $this->regularUser->id]);
    }

    // ── 2. Authorized user + unauthorized company → 403 ──

    public function test_authorized_user_with_unauthorized_company_returns_403(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyB->id]
        );

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Unauthorized. You do not have access to this company.']);
    }

    // ── 3. User from Company A attempting Company B → 403 ──

    public function test_company_a_user_cannot_access_company_b(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyB->id]
        );

        $response->assertStatus(403);
    }

    // ── 4. Multi-company user accessing both companies → 200 ──

    public function test_multi_company_user_can_access_company_a(): void
    {
        $response = $this->authRequest(
            $this->multiCompanyUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyA->id]
        );

        $response->assertStatus(200);
    }

    public function test_multi_company_user_can_access_company_b(): void
    {
        $response = $this->authRequest(
            $this->multiCompanyUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyB->id]
        );

        $response->assertStatus(200);
    }

    // ── 5. Invalid company → 404 ──

    public function test_invalid_company_id_returns_404(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => 99999]
        );

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Company not found.']);
    }

    // ── 6. Missing X-Company-Id → falls back to default company ──

    public function test_missing_header_falls_back_to_default_company(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me'
        );

        $response->assertStatus(200);
    }

    public function test_missing_header_no_default_company_returns_400(): void
    {
        $userNoDefault = User::create([
            'usercode' => 9004,
            'name' => 'No Default',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => null,
        ]);

        $response = $this->authRequest(
            $userNoDefault,
            'GET',
            '/api/me'
        );

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Company context required. Send X-Company-Id header or set a default company.']);
    }

    // ── 7. Super Admin → allowed to any company ──

    public function test_super_admin_can_access_company_a(): void
    {
        $response = $this->authRequest(
            $this->superAdmin,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyA->id]
        );

        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_company_b(): void
    {
        $response = $this->authRequest(
            $this->superAdmin,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyB->id]
        );

        $response->assertStatus(200);
    }

    // ── 8. CompanyContext is correctly set after authorization ──

    public function test_company_context_is_set_after_authorization(): void
    {
        $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyA->id]
        );

        $this->assertEquals($this->companyA->id, CompanyContext::id());
    }

    // ── 9. Non-numeric X-Company-Id → treated as missing ──

    public function test_non_numeric_company_id_treated_as_missing(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            ['X-Company-Id' => 'invalid']
        );

        // Falls back to user's default company_id
        $response->assertStatus(200);
    }

    // ── 10. Unauthenticated requests are not blocked by CompanyAccessMiddleware ──

    public function test_unauthenticated_request_not_blocked_by_company_access_middleware(): void
    {
        $response = $this->getJson('/api/health-check');

        $this->assertNotEquals(403, $response->status(), 'CompanyAccessMiddleware should not return 403 for unauthenticated requests');
        $this->assertNotEquals(400, $response->status(), 'CompanyAccessMiddleware should not return 400 for unauthenticated requests');
    }

    // ── 11. company_id in request body also works ──

    public function test_company_id_in_request_body_is_respected(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            [],
            ['company_id' => $this->companyA->id]
        );

        $response->assertStatus(200);
    }

    public function test_company_id_in_body_unauthorized_returns_403(): void
    {
        $response = $this->authRequest(
            $this->regularUser,
            'GET',
            '/api/me',
            [],
            ['company_id' => $this->companyB->id]
        );

        $response->assertStatus(403);
    }

    // ── 12. Regular admin (not super admin) is NOT treated as super admin ──

    public function test_regular_admin_is_not_super_admin(): void
    {
        $regularAdmin = User::create([
            'usercode' => 9005,
            'name' => 'Regular Admin',
            'password' => bcrypt('password'),
            'is_active' => true,
            'company_id' => $this->companyA->id,
        ]);
        $regularAdmin->roles()->attach(Role::where('name', 'Admin')->first());
        $regularAdmin->companies()->attach($this->companyA);

        $response = $this->authRequest(
            $regularAdmin,
            'GET',
            '/api/me',
            ['X-Company-Id' => $this->companyB->id]
        );

        // Regular admin with company_id assigned is NOT super admin — should be 403
        $response->assertStatus(403);
    }
}
