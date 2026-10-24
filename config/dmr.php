<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Sekolah
    |--------------------------------------------------------------------------
    |
    | Nama resmi yayasan dan unit sekolah belum dikonfirmasi. Nilai di bawah
    | ini hanya placeholder dan harus diganti sebelum aplikasi dipakai di
    | lingkungan produksi.
    |
    */

    'school_name' => env('DMR_SCHOOL_NAME', 'Sekolah DMR (placeholder)'),

    /*
    |--------------------------------------------------------------------------
    | Pernyataan Privasi
    |--------------------------------------------------------------------------
    |
    | Versi pernyataan privasi yang disimpan bersama persetujuan calon siswa.
    | Isi pernyataan final harus ditetapkan sekolah; nilai di sini hanya
    | penanda versi sehingga persetujuan dapat ditelusuri.
    |
    */

    'privacy_notice_version' => env('DMR_PRIVACY_NOTICE_VERSION', 'v1-placeholder'),

    /*
    |--------------------------------------------------------------------------
    | Kunci Fingerprint Identitas
    |--------------------------------------------------------------------------
    |
    | Fingerprint identitas memakai HMAC dengan kunci rahasia di luar
    | database. Kunci tidak boleh diubah setelah data pendaftaran terkumpul
    | karena perubahan kunci membuat perbandingan fingerprint tidak lagi
    | kecocokan. Bila belum diisi, application key dipakai sebagai cadangan.
    |
    */

    'fingerprint_key' => env('DMR_FINGERPRINT_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Pengaturan PPDB
    |--------------------------------------------------------------------------
    */

    'ppdb' => [
        'access_code_ttl_days' => (int) env('PPDB_ACCESS_CODE_TTL_DAYS', 30),
        'status_rate_limit' => (int) env('PPDB_STATUS_RATE_LIMIT', 10),
        'registration_number_prefix' => env('PPDB_REGISTRATION_PREFIX', 'PPDB'),
        'private_disk' => env('PPDB_PRIVATE_DISK', 'ppdb'),
        'max_upload_kb' => (int) env('PPDB_MAX_UPLOAD_KB', 4096),
    ],

];
