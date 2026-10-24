<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir pendaftaran calon siswa.
 *
 * Kolom yang diterbitkan server, seperti nomor pendaftaran dan kode akses,
 * tidak pernah ikut divalidasi karena tidak berasal dari input pengguna.
 */
class RegistrationRequest extends FormRequest
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
            'ppdb_period_id' => ['required', 'integer', Rule::exists('ppdb_periods', 'id')],
            'school_unit_id' => ['required', 'integer', Rule::exists('school_units', 'id')],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'candidate_name' => ['required', 'string', 'min:2', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'parent_name' => ['nullable', 'string', 'max:120'],
            'parent_phone' => ['required', 'string', 'min:6', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:150'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * Pesan validasi dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ppdb_period_id.required' => 'Periode pendaftaran belum dipilih.',
            'school_unit_id.required' => 'Jenjang sekolah belum dipilih.',
            'program_id.exists' => 'Program yang dipilih tidak tersedia.',
            'candidate_name.required' => 'Nama calon siswa wajib diisi.',
            'parent_phone.required' => 'Nomor kontak orang tua atau wali wajib diisi.',
            'consent.accepted' => 'Persetujuan pemrosesan data wajib diberikan.',
        ];
    }

    /**
     * Data yang diteruskan ke service layer.
     *
     * @return array<string, mixed>
     */
    public function registrationData(): array
    {
        return [
            'ppdb_period_id' => (int) $this->input('ppdb_period_id'),
            'school_unit_id' => (int) $this->input('school_unit_id'),
            'program_id' => $this->input('program_id') !== null
                ? (int) $this->input('program_id')
                : null,
            'candidate_name' => (string) $this->input('candidate_name'),
            'date_of_birth' => $this->input('date_of_birth'),
            'parent_name' => $this->input('parent_name'),
            'parent_phone' => (string) $this->input('parent_phone'),
            'parent_email' => $this->input('parent_email'),
            'data_consent_at' => now(),
            'privacy_notice_version' => config('dmr.privacy_notice_version'),
        ];
    }
}
