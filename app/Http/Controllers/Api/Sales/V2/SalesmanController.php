<?php

namespace App\Http\Controllers\Api\Sales\V2;

use App\Http\Controllers\Api\V2\BaseV2Controller;
use App\Http\Requests\Sales\V2\StoreSalesmanRequest;
use App\Http\Requests\Sales\V2\UpdateSalesmanRequest;
use App\Http\Resources\Sales\V2\SalesmanResource;
use App\Models\Salesman;
use App\Services\Sales\SalesmanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesmanController extends BaseV2Controller
{
    protected array $searchable = [
        'code', 'name', 'name_en', 'phone', 'mobile', 'email', 'national_id',
    ];

    protected array $filterable = [
        'company_id' => 'exact',
        'branch_id' => 'exact',
        'sales_team_id' => 'exact',
        'supervisor_id' => 'exact',
        'is_active' => 'boolean',
        'deleted' => 'deleted',
    ];

    protected array $sortable = [
        'id', 'code', 'name', 'name_en', 'phone', 'mobile',
        'target_amount', 'commission_rate', 'created_at', 'updated_at',
    ];

    public function __construct(
        protected SalesmanService $service,
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

        return $this->paginatedResponse($paginator, 'Salesmen retrieved successfully.');
    }

    public function store(StoreSalesmanRequest $request): JsonResponse
    {
        $salesman = $this->service->store($request->validated());

        return $this->successResponse(
            new SalesmanResource($salesman->load(['company', 'branch', 'salesTeam', 'supervisor'])),
            'Salesman created successfully.',
            201
        );
    }

    public function show(Salesman $salesman)
    {
        $salesman = $this->service->show($salesman->uuid);

        if (!$salesman) {
            return $this->errorResponse('Salesman not found.', 404);
        }

        return $this->successResponse(
            new SalesmanResource($salesman),
            'Salesman retrieved successfully.'
        );
    }

    public function update(UpdateSalesmanRequest $request, Salesman $salesman): JsonResponse
    {
        $updated = $this->service->update($salesman, $request->validated());

        return $this->successResponse(
            new SalesmanResource($updated->load(['company', 'branch', 'salesTeam', 'supervisor'])),
            'Salesman updated successfully.'
        );
    }

    public function destroy(Salesman $salesman): JsonResponse
    {
        $this->service->destroy($salesman);

        return $this->successResponse(null, 'Salesman deleted successfully.', 204);
    }

    public function restore(int $id): JsonResponse
    {
        $salesman = $this->service->restore($id);

        return $this->successResponse(
            new SalesmanResource($salesman),
            'Salesman restored successfully.'
        );
    }

    public function forceDelete(int $id): JsonResponse
    {
        $this->service->forceDelete($id);

        return $this->successResponse(null, 'Salesman permanently deleted.', 204);
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

    public function dropdown(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id') ?? $request->user()?->company_id;

        if (!$companyId) {
            return $this->errorResponse('company_id is required.', 422);
        }

        $salesmen = $this->service->dropdown($companyId);

        return $this->successResponse($salesmen, 'Salesmen dropdown retrieved successfully.');
    }
}
