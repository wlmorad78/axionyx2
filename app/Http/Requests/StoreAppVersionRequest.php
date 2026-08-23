<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAppVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => 'required|string',
            'build' => 'required|integer|min:1',
            'platform' => 'required|string|in:android,ios,windows',
            'download_url' => 'nullable|url',
            'force_update' => 'nullable|boolean',
            'release_notes' => 'nullable|array',
            'release_notes.*' => 'string',
            'release_date' => 'nullable|date',
            'minimum_supported_version' => 'nullable|string',
            'minimum_supported_build' => 'nullable|integer|min:0',
            'file_size' => 'nullable|integer|min:0',
            'checksum' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'Version number is required',
            'version.string' => 'Version must be a valid string',
            'build.required' => 'Build number is required',
            'build.integer' => 'Build must be an integer',
            'build.min' => 'Build must be at least 1',
            'platform.required' => 'Platform is required',
            'platform.in' => 'Platform must be either android, ios, or windows',
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
