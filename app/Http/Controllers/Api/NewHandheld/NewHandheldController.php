<?php
/**
 * =====================================================================
 * متحكم (Controller): NewHandheldController
 * الوحدة (Module): الأجهزة المحمولة (Handheld) (NewHandheld)
 * المورد (Resource): New Handheld
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "New Handheld" ضمن وحدة "الأجهزة المحمولة (Handheld)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\NewHandheld;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewHandheldController extends BaseApiController
{
    /**
     * عرض قائمة سجلات (New Handheld) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse([], 'New Handheld module - coming soon');
    }
}
