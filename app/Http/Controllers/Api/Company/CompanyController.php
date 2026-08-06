<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Company::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        $user = $request->user();
        if ($user && $user->company_id) {
            $query->where('id', $user->company_id);
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('company', 'store'));
        if (!isset($data['code']) || empty($data['code'])) {
            $data['code'] = $this->generateNextCode();
        }
        $company = Company::create($data);

        return response()->json($company, 201);
    }

    public function nextCode()
    {
        return response()->json(['next_code' => $this->generateNextCode()]);
    }

    private function generateNextCode(): string
    {
        $prefix = 'CMP-';
        $codes = Company::withTrashed()
            ->where('code', 'like', "$prefix%")
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            $num = (int) substr($code, strlen($prefix));
            if ($num > $max) $max = $num;
        }

        return $prefix . str_pad($max + 1, 5, '0', STR_PAD_LEFT);
    }

    public function show(Company $company)
    {
        return $company->load(['currency', 'country', 'governorate', 'city', 'area', 'street']);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate(ValidationRules::for('company', 'update', $company));
        $company->update($data);

        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->restore();

        return response()->json($company);
    }

    public function forceDelete(int $id)
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $company->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('company', 'store');
    }
}
