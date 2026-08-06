<?php

namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\WithholdingTaxCertificate;
use Illuminate\Http\Request;

class WithholdingTaxCertificateController extends Controller
{
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

    public function show(WithholdingTaxCertificate $withholdingTaxCertificate)
    {
        return response()->json($withholdingTaxCertificate);
    }

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

    public function destroy(WithholdingTaxCertificate $withholdingTaxCertificate)
    {
        $withholdingTaxCertificate->delete();

        return response()->json(['message' => 'Withholding tax certificate deleted successfully.']);
    }
}
