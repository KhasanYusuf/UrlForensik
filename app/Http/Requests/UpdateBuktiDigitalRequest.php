<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuktiDigitalRequest extends FormRequest
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
            'id_kasus' => 'required|exists:kasus,id_kasus',
            'jenis_bukti' => 'required|string|max:255',
            'file_url' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,zip,rar', // Optional on update
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
            'id_kasus.required' => 'Kasus harus dipilih.',
            'id_kasus.exists' => 'Kasus yang dipilih tidak valid.',
            'jenis_bukti.required' => 'Jenis bukti harus diisi.',
            'jenis_bukti.max' => 'Jenis bukti maksimal 255 karakter.',
            'file_url.file' => 'File tidak valid.',
            'file_url.max' => 'Ukuran file maksimal 10MB.',
            'file_url.mimes' => 'Format file harus: jpg, jpeg, png, pdf, doc, docx, zip, atau rar.',
        ];
    }
}
