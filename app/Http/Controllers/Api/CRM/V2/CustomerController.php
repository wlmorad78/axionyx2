<?php

namespace App\Http\Controllers\Api\CRM\V2;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Http\Requests\CRM\V2\StoreCustomerRequest;
use App\Http\Requests\CRM\V2\UpdateCustomerRequest;
use App\Http\Resources\CRM\V2\CustomerResource;
use App\Models\Customer;
use App\Services\CRM\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseV2Controller
{
    protected array $searchable = [
        'code', 'name_ar', 'name_en', 'national_id', 'tax_number',
        'mobile', 'phone', 'email', 'responsible_person',
    ];

    protected array $filterable = [
        'company_id' => 'exact',
        'branch_id' => 'exact',
        'customer_group_id' => 'exact',
        'customer_class_id' => 'exact',
        'customer_type_id' => 'exact',
        'customer_account_type_id' => 'exact',
        'governorate_id' => 'exact',
        'city_id' => 'exact',
        'area_id' => 'exact',
        'is_active' => 'boolean',
        'deleted' => 'deleted',
    ];

    protected array $sortable = [
        'id', 'code', 'name_ar', 'name_en', 'mobile', 'phone',
        'credit_limit', 'created_at', 'updated_at',
    ];

    public function __construct(
        protected CustomerService $service,
    ) {}

    public function index(Request $request)
    {
        $query = $this->service->index(
            filters: $request->only(array_keys($this->filterable)),
            search: $request->input('search'),
            sortField: $request->input('sort_field', 'created_at'),
            sortDirection: $request->input('sort_direction', 'desc'),
        );

        $query = $this->applySearch($query, $request);
        $query = $this->applySorting($query, $request);

        $paginator = $this->applyPagination($query, $request);

        return $this->paginatedResponse($paginator, 'Customers retrieved successfully.');
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->store($request->validated());

        return $this->successResponse(
            new CustomerResource($customer->load(['company', 'defaultSalesman', 'customerGroup', 'customerClass', 'customerType', 'customerAccountType'])),
            'Customer created successfully.',
            201
        );
    }

    public function show(Customer $customer)
    {
        $customer = $this->service->show($customer->id);

        if (!$customer) {
            return $this->errorResponse('Customer not found.', 404);
        }

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer retrieved successfully.'
        );
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $updated = $this->service->update($customer, $request->validated());

        return $this->successResponse(
            new CustomerResource($updated->load(['company', 'defaultSalesman', 'customerGroup', 'customerClass', 'customerType', 'customerAccountType'])),
            'Customer updated successfully.'
        );
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->service->destroy($customer);

        return $this->successResponse(null, 'Customer deleted successfully.', 204);
    }

    public function restore(int $id): JsonResponse
    {
        $customer = $this->service->restore($id);

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer restored successfully.'
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->service->forceDelete($id);

        return $this->successResponse(null, 'Customer permanently deleted.', 204);
    }

    public function nextCode(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
        }

        $code = $this->service->nextCode($companyId);

        return $this->successResponse([
            'code' => $code,
            'next_code' => $code,
        ], 'Next code generated.');
    }

    public function schema(): JsonResponse
    {
        return $this->successResponse(
            (new StoreCustomerRequest)->rules(),
            'Customer validation schema.'
        );
    }
}
