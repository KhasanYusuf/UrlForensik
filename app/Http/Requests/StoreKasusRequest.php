<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKasusRequest extends FormRequest
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
            'id_site' => 'required|exists:monitored_sites,id_site',
            'jenis_kasus' => 'required|string|max:255',
            'tanggal_kejadian' => 'required|date',
            'deskripsi_kasus' => 'required|string',
            'status_kasus' => 'required|in:Open,Closed',
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
            'id_site.required' => 'Monitored site harus dipilih.',
            'id_site.exists' => 'Monitored site yang dipilih tidak valid.',
            'jenis_kasus.required' => 'Jenis kasus harus diisi.',
            'tanggal_kejadian.required' => 'Tanggal kejadian harus diisi.',
            'tanggal_kejadian.date' => 'Format tanggal tidak valid.',
            'deskripsi_kasus.required' => 'Deskripsi kasus harus diisi.',
            'status_kasus.required' => 'Status kasus harus dipilih.',
            'status_kasus.in' => 'Status kasus harus Open atau Closed.',
        ];
    }
}
