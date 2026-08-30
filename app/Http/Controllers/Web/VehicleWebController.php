<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleWebController
 * الوحدة (Module): واجهات الويب (Views) (Web)
 * المورد (Resource): Vehicle Web
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Web" ضمن وحدة "واجهات الويب (Views)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleWebController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Web) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Vehicle::with(['vehicleType'])->orderByDesc('id');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->vehicle_type_id) {
            $query->where('vehicle_type_id', $request->vehicle_type_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('vehicle_code', 'like', "%$s%")
                  ->orWhere('plate_number', 'like', "%$s%")
                  ->orWhere('model', 'like', "%$s%");
            });
        }

        $vehicles = $query->paginate(15);
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total' => Vehicle::count(),
            'active' => Vehicle::where('status', 'active')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
            'inactive' => Vehicle::where('status', 'inactive')->count(),
        ];

        return view('vehicles.index', compact('vehicles', 'vehicleTypes', 'stats'));
    }

    /**
     * عرض نموذج / بيانات إنشاء سجل جديد لـ (Vehicle Web).
     */
    public function create()
    {
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();
        $nextCode = Vehicle::withTrashed()
            ->where('vehicle_code', 'like', 'VH-%')
            ->orderByRaw("CAST(SUBSTRING(vehicle_code, 4) AS UNSIGNED) DESC")
            ->value('vehicle_code');

        if ($nextCode && preg_match('/^VH-(\d+)$/', $nextCode, $m)) {
            $nextCode = 'VH-' . str_pad((int) $m[1] + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextCode = 'VH-001';
        }

        return view('vehicles.create', compact('vehicleTypes', 'nextCode'));
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Web) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_code' => 'nullable|string|max:30|unique:vehicles,vehicle_code',
            'plate_number' => 'required|string|max:30',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'model' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $user = Auth::user();
        $vehicle = Vehicle::create([
            'company_id' => $user->company_id,
            'vehicle_code' => $request->vehicle_code,
            'plate_number' => $request->plate_number,
            'vehicle_type_id' => $request->vehicle_type_id,
            'model' => $request->model,
            'year' => $request->year,
            'capacity' => $request->capacity,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('vehicles.show', $vehicle->id)
            ->with('success', "تم إنشاء المركبة {$vehicle->vehicle_code} بنجاح");
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Web) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['vehicleType', 'documents', 'assignments.driver', 'maintenance', 'tires', 'batteries', 'insurance']);

        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * عرض نموذج تعديل سجل موجود من (Vehicle Web).
     */
    public function edit(Vehicle $vehicle)
    {
        $vehicleTypes = VehicleType::where('is_active', true)->orderBy('name')->get();

        return view('vehicles.edit', compact('vehicle', 'vehicleTypes'));
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Web) بناءً على المعرّف.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_code' => 'nullable|string|max:30|unique:vehicles,vehicle_code,' . $vehicle->id,
            'plate_number' => 'required|string|max:30',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'model' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $vehicle->update([
            'vehicle_code' => $request->vehicle_code,
            'plate_number' => $request->plate_number,
            'vehicle_type_id' => $request->vehicle_type_id,
            'model' => $request->model,
            'year' => $request->year,
            'capacity' => $request->capacity,
            'status' => $request->status,
        ]);

        return redirect()
            ->route('vehicles.show', $vehicle->id)
            ->with('success', "تم تحديث بيانات المركبة {$vehicle->vehicle_code} بنجاح");
    }

    /**
     * حذف سجل من (Vehicle Web) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()
            ->route('vehicles.index')
            ->with('success', "تم حذف المركبة {$vehicle->vehicle_code} بنجاح");
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Web) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($id);
        $vehicle->restore();
        return redirect()
            ->route('vehicles.index')
            ->with('success', "تم استعادة المركبة {$vehicle->vehicle_code} بنجاح");
    }
}
