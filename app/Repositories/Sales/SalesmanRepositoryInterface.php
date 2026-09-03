<?php

namespace App\Repositories\Sales;

use App\Models\Salesman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface SalesmanRepositoryInterface
{
    public function query(): Builder;

    public function findById(int $id, array $with = []): ?Salesman;

    public function findByUuid(string $uuid, array $with = []): ?Salesman;

    public function create(array $data): Salesman;

    public function update(Salesman $salesman, array $data): Salesman;

    public function delete(Salesman $salesman): bool;

    public function restore(int $id): Salesman;

    public function forceDelete(int $id): bool;

    public function nextCode(int $companyId): string;

    public function getDropdown(int $companyId): Collection;
}
