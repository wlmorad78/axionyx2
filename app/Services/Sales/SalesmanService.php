<?php

namespace App\Services\Sales;

use App\Events\Sales\SalesmanCreated;
use App\Events\Sales\SalesmanDeleted;
use App\Events\Sales\SalesmanRestored;
use App\Events\Sales\SalesmanUpdated;
use App\Models\Salesman;
use App\Repositories\Sales\SalesmanRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesmanService
{
    public function __construct(
        protected SalesmanRepositoryInterface $repo,
    ) {}

    public function index(array $filters = [], ?string $search = null, string $sortField = 'created_at', string $sortDirection = 'desc')
    {
        $query = $this->repo->query();

        if (!empty($filters['deleted'])) {
            $query = match ($filters['deleted']) {
                'only' => $query->onlyTrashed(),
                'with' => $query->withTrashed(),
                default => $query,
            };
            unset($filters['deleted']);
        }

        foreach ($filters as $field => $value) {
            if ($value === null) continue;

            $query->when(
                in_array($field, ['is_active']),
                fn($q) => $q->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                fn($q) => $q->where($field, $value)
            );
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sortField, $sortDirection);
    }

    public function store(array $data): Salesman
    {
        return DB::transaction(function () use ($data) {
            $salesman = $this->repo->create($data);
            SalesmanCreated::dispatch($salesman, $data['company_id']);
            return $salesman;
        });
    }

    public function show(string $uuid): ?Salesman
    {
        return $this->repo->findByUuid($uuid, [
            'company', 'branch', 'employee', 'salesTeam', 'supervisor',
        ]);
    }

    public function update(Salesman $salesman, array $data): Salesman
    {
        return DB::transaction(function () use ($salesman, $data) {
            $oldAttributes = $salesman->getOriginal();
            $updated = $this->repo->update($salesman, $data);
            SalesmanUpdated::dispatch($updated, $oldAttributes);
            return $updated;
        });
    }

    public function destroy(Salesman $salesman): bool
    {
        return DB::transaction(function () use ($salesman) {
            $result = $this->repo->delete($salesman);
            SalesmanDeleted::dispatch($salesman);
            return $result;
        });
    }

    public function restore(int $id): Salesman
    {
        return DB::transaction(function () use ($id) {
            $salesman = $this->repo->restore($id);
            SalesmanRestored::dispatch($salesman);
            return $salesman;
        });
    }

    public function forceDelete(int $id): bool
    {
        return $this->repo->forceDelete($id);
    }

    public function nextCode(int $companyId): string
    {
        return $this->repo->nextCode($companyId);
    }

    public function dropdown(int $companyId)
    {
        return $this->repo->getDropdown($companyId);
    }
}
