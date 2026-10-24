<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi kredensial pemeriksaan status pendaftaran.
 */
class StatusCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:40'],
            'access_code' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_number.required' => 'Nomor pendaftaran wajib diisi.',
            'access_code.required' => 'Kode akses wajib diisi.',
        ];
    }
}
