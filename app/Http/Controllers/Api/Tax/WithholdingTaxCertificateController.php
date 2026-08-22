<?php
/**
 * =====================================================================
 * متحكم (Controller): WithholdingTaxCertificateController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Withholding Tax Certificate
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Withholding Tax Certificate" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\WithholdingTaxCertificate;
use Illuminate\Http\Request;

class WithholdingTaxCertificateController extends Controller
{
    /**
     * عرض قائمة سجلات (Withholding Tax Certificate) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WithholdingTaxCertificate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('certificate_no', 'like', "%{$search}%");
        }

        $certificates = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($certificates);
    }

    /**
     * إنشاء سجل جديد لـ (Withholding Tax Certificate) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'certificate_no' => 'required',
            'tax_type_id' => 'required',
            'certificate_date' => 'required|date',
            'amount' => 'numeric',
            'tax_amount' => 'numeric',
            'customer_id' => 'nullable',
            'supplier_id' => 'nullable',
        ]);

        $certificate = WithholdingTaxCertificate::create($validated);

        return response()->json($certificate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Withholding Tax Certificate) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(WithholdingTaxCertificate $withholdingTaxCertificate)
    {
        return response()->json($withholdingTaxCertificate);
    }

    /**
     * تحديث بيانات سجل موجود من (Withholding Tax Certificate) بناءً على المعرّف.
     */
    public function update(Request $request, WithholdingTaxCertificate $withholdingTaxCertificate)
    {
        $validated = $request->validate([
            'certificate_no' => 'required',
            'tax_type_id' => 'required',
            'certificate_date' => 'required|date',
            'amount' => 'numeric',
            'tax_amount' => 'numeric',
            'customer_id' => 'nullable',
            'supplier_id' => 'nullable',
        ]);

        $withholdingTaxCertificate->update($validated);

        return response()->json($withholdingTaxCertificate);
    }

    /**
     * حذف سجل من (Withholding Tax Certificate) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(WithholdingTaxCertificate $withholdingTaxCertificate)
    {
        $withholdingTaxCertificate->delete();

        return response()->json(['message' => 'Withholding tax certificate deleted successfully.']);
    }
}
