<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingPhotoController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Photo
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Photo" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingPhoto;
use Illuminate\Http\Request;

class MerchandisingPhotoController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Photo) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingPhoto::with('visit');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('photo_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('merchandising_visit_id')) $query->where('merchandising_visit_id', $request->merchandising_visit_id);
        if ($request->filled('photo_type')) $query->where('photo_type', $request->photo_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Photo) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'merchandising_visit_id' => 'required|exists:merchandising_visits,id',
            'photo_type' => 'required|in:STORE_FRONT,SHELF,FRIDGE,DISPLAY,PROMOTION',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo = MerchandisingPhoto::create($data);
        return response()->json($photo, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Photo) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MerchandisingPhoto::with('visit')->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Photo) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $photo = MerchandisingPhoto::findOrFail($id);

        $data = $request->validate([
            'merchandising_visit_id' => 'sometimes|required|exists:merchandising_visits,id',
            'photo_type' => 'sometimes|required|in:STORE_FRONT,SHELF,FRIDGE,DISPLAY,PROMOTION',
            'file_path' => 'sometimes|required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $photo->update($data);
        return $photo;
    }

    /**
     * حذف سجل من (Merchandising Photo) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $photo = MerchandisingPhoto::findOrFail($id);
        $photo->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Photo) وإعادته للعمل.
     */
    public function restore($id)
    {
        $photo = MerchandisingPhoto::withTrashed()->findOrFail($id);
        $photo->restore();
        return $photo;
    }

    /**
     * حذف نهائي للسجل من (Merchandising Photo) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $photo = MerchandisingPhoto::withTrashed()->findOrFail($id);
        $photo->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
