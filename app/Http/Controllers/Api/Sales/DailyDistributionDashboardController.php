<?php
/**
 * =====================================================================
 * متحكم (Controller): DailyDistributionDashboardController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Daily Distribution Dashboard
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Daily Distribution Dashboard" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyDistributionDashboard;
use Illuminate\Http\Request;

class DailyDistributionDashboardController extends Controller
{
    /**
     * عرض قائمة سجلات (Daily Distribution Dashboard) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DailyDistributionDashboard::with(['company', 'branch', 'salesRep', 'route']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('dashboard_date')) {
            $query->where('dashboard_date', $request->dashboard_date);
        }
        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%");
            });
        }

        $dashboards = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($dashboards);
    }

    /**
     * إنشاء سجل جديد لـ (Daily Distribution Dashboard) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            \App\Support\ValidationRules::rules('daily_distribution_dashboard', false)
        );

        $dashboard = DailyDistributionDashboard::create($validated);

        return response()->json($dashboard->load(['company', 'branch', 'salesRep', 'route']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Daily Distribution Dashboard) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $dashboard = DailyDistributionDashboard::with(['company', 'branch', 'salesRep', 'route'])->findOrFail($id);

        return response()->json($dashboard);
    }

    /**
     * تحديث بيانات سجل موجود من (Daily Distribution Dashboard) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $dashboard = DailyDistributionDashboard::findOrFail($id);

        $validated = $request->validate(
            \App\Support\ValidationRules::rules('daily_distribution_dashboard', true)
        );

        $dashboard->update($validated);

        return response()->json($dashboard->load(['company', 'branch', 'salesRep', 'route']));
    }

    /**
     * حذف سجل من (Daily Distribution Dashboard) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $dashboard = DailyDistributionDashboard::findOrFail($id);
        $dashboard->delete();

        return response()->json(['message' => 'Dashboard deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Daily Distribution Dashboard) وإعادته للعمل.
     */
    public function restore($id)
    {
        $dashboard = DailyDistributionDashboard::withTrashed()->findOrFail($id);
        $dashboard->restore();

        return response()->json(['message' => 'Dashboard restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Daily Distribution Dashboard) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $dashboard = DailyDistributionDashboard::withTrashed()->findOrFail($id);
        $dashboard->forceDelete();

        return response()->json(['message' => 'Dashboard permanently deleted']);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Daily Distribution Dashboard).
     */
    public function schema()
    {
        return response()->json([
            'columns' => [
                'id' => 'integer',
                'company_id' => 'integer',
                'branch_id' => 'integer',
                'dashboard_date' => 'date',
                'sales_rep_id' => 'integer',
                'route_id' => 'integer',
                'planned_customers' => 'integer',
                'visited_customers' => 'integer',
                'invoices_count' => 'integer',
                'sales_amount' => 'decimal',
                'returns_amount' => 'decimal',
                'collections_amount' => 'decimal',
                'loaded_amount' => 'decimal',
                'settled_amount' => 'decimal',
                'cash_difference' => 'decimal',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
                'deleted_at' => 'timestamp',
            ],
        ]);
    }
}
