<?php

namespace App\Http\Requests\CRM\V2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImportCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required_without:rows', 'file', 'mimes:csv,txt', 'max:10240'],
            'rows' => ['required_without:file', 'array', 'min:1'],
            'rows.*' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required_without' => 'Either a CSV file or JSON rows are required.',
            'rows.required_without' => 'Either a CSV file or JSON rows are required.',
            'file.mimes' => 'The file must be a CSV or TXT file.',
            'file.max' => 'The file size must not exceed 10MB.',
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
