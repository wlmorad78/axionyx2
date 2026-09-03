<?php

namespace App\Repositories\Sales;

use App\Models\Salesman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesmanRepository implements SalesmanRepositoryInterface
{
    public function query(): Builder
    {
        return Salesman::withoutGlobalScope(\App\Scopes\BranchIsolationScope::class);
    }

    public function findById(int $id, array $with = []): ?Salesman
    {
        return $this->query()->with($with)->find($id);
    }

    public function findByUuid(string $uuid, array $with = []): ?Salesman
    {
        return $this->query()->with($with)->where('uuid', $uuid)->first();
    }

    public function create(array $data): Salesman
    {
        $data['uuid'] = $data['uuid'] ?? \Illuminate\Support\Str::uuid()->toString();
        return Salesman::create($data);
    }

    public function update(Salesman $salesman, array $data): Salesman
    {
        $salesman->update($data);
        return $salesman->fresh();
    }

    public function delete(Salesman $salesman): bool
    {
        return $salesman->delete();
    }

    public function restore(int $id): Salesman
    {
        $model = Salesman::onlyTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete(int $id): bool
    {
        return Salesman::onlyTrashed()->findOrFail($id)->forceDelete();
    }

    public function nextCode(int $companyId): string
    {
        $maxSeq = Salesman::withTrashed()
            ->where('company_id', $companyId)
            ->where('code', 'like', 'SM-%')
            ->selectRaw("MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_seq")
            ->value('max_seq') ?? 0;

        $nextSeq = $maxSeq + 1;

        return 'SM-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }

    public function getDropdown(int $companyId): Collection
    {
        return $this->query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'name_en', 'phone', 'mobile']);
    }
}
