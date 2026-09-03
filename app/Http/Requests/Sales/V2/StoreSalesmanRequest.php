<?php

namespace App\Http\Requests\Sales\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreSalesmanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'sales_team_id' => ['nullable', 'exists:sales_teams,id'],
            'supervisor_id' => ['nullable', 'exists:salesmen,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('salesmen', 'code')->where(fn ($q) => $q->where('company_id', $this->company_id))],
            'name' => ['required', 'string', 'max:255'],
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
