<?php

namespace App\Http\Requests;

use App\Models\ContentItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir konten CMS.
 *
 * Jenis konten hanya boleh berasal dari daftar yang tersedia. Pada
 * pembaruan, jenis konten tidak dapat dipindahkan karena halaman publik
 * sudah terikat pada jenis tersebut.
 */
class ContentItemRequest extends FormRequest
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
            'content_type' => [
                Rule::requiredIf($this->route('item') === null),
                'nullable',
                'string',
                Rule::in(array_keys(ContentItem::TYPES)),
            ],
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content_type.required' => 'Jenis konten wajib dipilih.',
            'content_type.in' => 'Jenis konten tidak dikenali.',
            'title.required' => 'Judul wajib diisi.',
            'title.max' => 'Judul maksimal 180 karakter.',
            'excerpt.max' => 'Ringkasan maksimal 500 karakter.',
            'body.max' => 'Isi konten maksimal 20.000 karakter.',
        ];
    }
}
