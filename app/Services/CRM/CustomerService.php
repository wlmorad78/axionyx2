<?php

namespace App\Services\CRM;

use App\Events\CRM\CustomerCreated;
use App\Events\CRM\CustomerDeleted;
use App\Events\CRM\CustomerRestored;
use App\Events\CRM\CustomerUpdated;
use App\Models\Customer;
use App\Repositories\CRM\CustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        protected CustomerRepositoryInterface $repo,
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
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('tax_number', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy($sortField, $sortDirection);
    }

    public function store(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->repo->create($data);
            CustomerCreated::dispatch($customer, $data['company_id']);
            return $customer;
        });
    }

    public function show(int $id): ?Customer
    {
        return $this->repo->getWithRelations($id, [
            'company', 'defaultSalesman', 'customerGroup', 'customerClass', 'customerType',
            'customerAccountType', 'tradeProgramType', 'governorate',
            'city', 'area', 'addresses', 'contacts',
        ]);
    }

    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $oldAttributes = $customer->getOriginal();
            $updated = $this->repo->update($customer, $data);
            CustomerUpdated::dispatch($updated, $oldAttributes);
            return $updated;
        });
    }

    public function destroy(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer) {
            $result = $this->repo->delete($customer);
            CustomerDeleted::dispatch($customer);
            return $result;
        });
    }

    public function restore(int $id): Customer
    {
        return DB::transaction(function () use ($id) {
            $customer = $this->repo->restore($id);
            CustomerRestored::dispatch($customer);
            return $customer;
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

    public function accounts(int $companyId, ?string $search = null)
    {
        return $this->repo->getAccounts($companyId, $search);
    }

    public function ledger(int $customerId, ?string $from = null, ?string $to = null): array
    {
        return $this->repo->getLedgerData($customerId, $from, $to);
    }

    public function importCsv(array $rows, int $companyId): array
    {
        return DB::transaction(function () use ($rows, $companyId) {
            return $this->repo->importFromCsv($rows, $companyId);
        });
    }

    public function importJson(array $rows, int $companyId): array
    {
        return DB::transaction(function () use ($rows, $companyId) {
            return $this->repo->importFromJson($rows, $companyId);
        });
    }

    public function export(int $companyId, ?string $search = null): array
    {
        return $this->repo->exportData($companyId, $search);
    }

    public function lastInvoices(array $customerIds, int $companyId)
    {
        return $this->repo->getLastInvoices($customerIds, $companyId);
    }
}
