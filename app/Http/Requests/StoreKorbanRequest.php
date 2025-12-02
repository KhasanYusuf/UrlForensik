<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKorbanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_url' => 'required|url|max:255',
            'ip_address' => 'nullable|ip',
            'status' => 'nullable|in:UP,DOWN,DEFACED',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_url.required' => 'Site URL harus diisi.',
            'site_url.url' => 'Format Site URL tidak valid.',
            'site_url.max' => 'Site URL maksimal 255 karakter.',
            'ip_address.ip' => 'Format IP address tidak valid.',
        ];
    }
}
