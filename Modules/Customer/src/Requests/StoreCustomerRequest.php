<?php

namespace App\Modules\Customer\src\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
            'default_salesman_id' => ['nullable', 'exists:salesmen,id'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'customer_class_id' => ['nullable', 'exists:customer_classes,id'],
            'customer_type_id' => ['nullable', 'exists:customer_types,id'],
            'customer_account_type_id' => ['nullable', 'exists:customer_account_types,id'],
            'trade_program_type_id' => ['nullable', 'exists:trade_program_types,id'],
            'cus_sings' => ['sometimes', 'boolean'],
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->where(fn ($q) => $q->where('company_id', $this->company_id))],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'responsible_person' => ['nullable', 'string', 'max:255'],
            'location_mark' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_register' => ['nullable', 'string', 'max:50'],
            'governorate_id' => ['nullable', 'exists:governorates,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'area_id' => ['nullable', 'exists:districts,id'],
            'sales_territory_id' => ['nullable', 'exists:sales_territories,id'],
            'route_line_id' => ['nullable', 'exists:routes,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'has_whatsapp' => ['sometimes', 'boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'payment_term_days' => ['sometimes', 'integer', 'min:0'],
            'account_type' => ['nullable', 'string', 'max:50'],
            'trade_program_type' => ['nullable', 'string', 'max:50'],
            'pos_material' => ['nullable', 'string', 'max:255'],
            'pos_code' => ['nullable', 'string', 'max:50'],
            'address_line' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'average_withdrawals' => ['sometimes', 'numeric', 'min:0'],
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
