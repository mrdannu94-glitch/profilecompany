<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi unggah dokumen pendaftaran.
 *
 * Pemeriksaan tipe berkas dan ukuran yang mengikuti persyaratan dilakukan
 * pada service layer karena bergantung pada data persyaratan.
 */
class DocumentUploadRequest extends FormRequest
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
        $maxKilobytes = (int) config('dmr.ppdb.max_upload_kb', 4096);

        return [
            'ppdb_requirement_id' => ['required', 'integer', Rule::exists('ppdb_requirements', 'id')],
            'document' => ['required', 'file', 'max:'.$maxKilobytes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ppdb_requirement_id.required' => 'Persyaratan dokumen belum dipilih.',
            'document.required' => 'Berkas dokumen wajib diunggah.',
            'document.max' => 'Ukuran berkas melebihi batas yang diizinkan.',
        ];
    }
}
