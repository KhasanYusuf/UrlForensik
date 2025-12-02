<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTindakanForensikRequest extends FormRequest
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
            'jenis_tindakan' => 'required|string|in:Analysis,Recovery,Preservation',
            'waktu_pelaksanaan' => 'required|date',
            'lokasi_tindakan' => 'nullable|string|max:255',
            'metode_forensik' => 'required|string|max:255',
            'hasil_tindakan' => 'required|string',
            'petugas_forensik' => 'required|string|max:255',
            'status_tindakan' => 'required|in:Planned,InProgress,Completed',
            'catatan' => 'nullable|string',
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
            'jenis_tindakan.required' => 'Jenis tindakan harus diisi.',
            'jenis_tindakan.in' => 'Jenis tindakan tidak valid.',
            'waktu_pelaksanaan.required' => 'Waktu pelaksanaan harus diisi.',
            'waktu_pelaksanaan.date' => 'Format waktu pelaksanaan tidak valid.',
            'petugas_forensik.required' => 'Petugas forensik harus diisi.',
            'status_tindakan.required' => 'Status tindakan harus dipilih.',
            'status_tindakan.in' => 'Status tindakan tidak valid.',
        ];
    }
}
