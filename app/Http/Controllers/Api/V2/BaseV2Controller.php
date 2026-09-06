<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseV2Controller extends BaseApiController
{
    protected array $searchable = [];

    protected array $filterable = [];

    protected array $sortable = [];

    protected int $maxPerPage = 100;

    protected function applySearch($query, Request $request, array $searchableFields = []): mixed
    {
        $search = $request->input('search');
        $fields = !empty($searchableFields) ? $searchableFields : $this->searchable;

        if (!$search || empty($fields)) {
            return $query;
        }

        $query->where(function ($q) use ($search, $fields) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'LIKE', "%{$search}%");
            }
        });

        return $query;
    }

    protected function applySorting($query, Request $request, array $allowedSorts = []): mixed
    {
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $fields = !empty($allowedSorts) ? $allowedSorts : $this->sortable;

        if (!in_array($sortField, $fields)) {
            $sortField = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        return $query->orderBy($sortField, $sortDirection);
    }

    protected function applyFilters($query, Request $request): mixed
    {
        foreach ($this->filterable as $field => $type) {
            $value = $request->input($field);

            if ($value === null) {
                continue;
            }

            $query = match ($type) {
                'boolean' => $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                'date_from' => $query->where($field, '>=', $value),
                'date_to' => $query->where($field, '<=', $value),
                'deleted' => match ($value) {
                    'only' => $query->onlyTrashed(),
                    'with' => $query->withTrashed(),
                    default => $query,
                },
                default => $query->where($field, $value),
            };
        }

        return $query;
    }

    protected function applyPagination($query, Request $request, int $defaultPerPage = 25): mixed
    {
        $perPage = min(
            max((int) $request->input('per_page', $defaultPerPage), 1),
            $this->maxPerPage
        );

        return $query->paginate($perPage);
    }
}
