<?php
/**
 * =====================================================================
 * متحكم (Controller): BaseApiController
 * الوحدة (Module): واجهة برمجة التطبيقات (Api)
 * المورد (Resource): Base Api
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Base Api" ضمن وحدة "واجهة برمجة التطبيقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class BaseApiController extends Controller
{
    /**
     * دالة معالجة: successResponse — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function successResponse($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * دالة معالجة: errorResponse — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * دالة معالجة: paginatedResponse — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * دالة معالجة: applySearch — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function applySearch($query, Request $request, array $searchableFields): mixed
    {
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        return $query;
    }

    /**
     * دالة معالجة: applySorting — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function applySorting($query, Request $request, array $allowedSorts = []): mixed
    {
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * دالة معالجة: applyPagination — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function applyPagination($query, Request $request, int $defaultPerPage = 25): mixed
    {
        $perPage = $request->input('per_page', $defaultPerPage);
        return $query->paginate($perPage);
    }

    /**
     * دالة معالجة: transaction — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Base Api).
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
