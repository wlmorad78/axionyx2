<?php

namespace App\Modules\Distribution\src\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateSalesmanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $salesman = $this->route('salesman');

        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'sales_team_id' => ['nullable', 'exists:sales_teams,id'],
            'supervisor_id' => ['nullable', 'exists:salesmen,id'],
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('salesmen', 'code')->where(fn ($q) => $q->where('company_id', $this->company_id))->ignore($salesman?->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'hire_date' => ['nullable', 'date'],
            'target_amount' => ['sometimes', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'commission_value' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
