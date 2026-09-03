<?php

namespace App\Repositories\CRM;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface CustomerRepositoryInterface
{
    public function query(): Builder;

    public function findById(int $id, array $with = []): ?Customer;

    public function findByCode(string $code, int $companyId): ?Customer;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): Customer;

    public function delete(Customer $customer): bool;

    public function restore(int $id): Customer;

    public function forceDelete(int $id): bool;

    public function nextCode(int $companyId): string;

    public function getWithRelations(int $id, array $with = []): ?Customer;

    public function getAccounts(int $companyId, ?string $search = null): Collection;

    public function getLedgerData(int $customerId, ?string $from = null, ?string $to = null): array;

    public function importFromCsv(array $rows, int $companyId): array;

    public function importFromJson(array $rows, int $companyId): array;

    public function exportData(int $companyId, ?string $search = null): array;

    public function getLastInvoices(array $customerIds, int $companyId): Collection;
}
