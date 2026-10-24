# Project Audit — Kondisi Awal Repo Laravel (Revisi)

**Tanggal audit:** 18 Oktober 2026  
**Dokumen acuan:** `PRD_Website_DMR_OpenCode.md`  
**Status:** Revisi setelah review rancangan  
**Tujuan:** Catat kondisi awal sebelum implementasi Fase 1–6. Tanpa rahasia/secret.

---

## 1. Ringkasan Repo

| Item | Nilai |
|------|-------|
| Nama proyek (composer) | `laravel/laravel` |
| Framework | Laravel 13 (`^13.17`) |
| PHP | `^8.3` |
| Database default (`.env`) | SQLite (`database/database.sqlite`) |
| Target DB sesuai PRD | MySQL/MariaDB via Laragon |
| Frontend | Vite 8, Tailwind CSS v4, Blade |
| Entry point Vite | `resources/css/app.css`, `resources/js/app.js` |
| CSS Framework | Tailwind v4 (sudah terpasang via `@tailwindcss/vite`) |
| JS Framework | Tidak ada (app.js kosong) |
| Testing | PHPUnit 12 (`phpunit.xml`), in-memory SQLite untuk test |
| Formatter | Laravel Pint (`vendor/bin/pint`) |
| Git | Belum terdeteksi sebagai repo git (harus di-init jika diperlukan) |
| `.env` | Ada di lokal, **tidak boleh di-commit**, sudah tercantum di `.gitignore` |
| `.env.example` | Ada, tanpa secret |

---

## 2. Dependensi Terpasang

### Backend (`composer.json`)
- `laravel/framework: ^13.17`
- `laravel/tinker: ^3.0`
- Dev: `fakerphp/faker`, `laravel/pail`, `laravel/pao`, `laravel/pint`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit: ^12.5.12`
- `composer.lock` tersedia (dependencies sudah di-lock)

### Frontend (`package.json`)
- `@tailwindcss/vite: ^4.0.0`
- `concurrently: ^10.0.3`
- `laravel-vite-plugin: ^3.1`
- `tailwindcss: ^4.0.0`
- `vite: ^8.0.0`
- Optional: `@laravel/multiplex: ^0.4.1`
- `.npmrc`: `ignore-scripts=true`, `audit=true`

### Catatan
Tidak ada paket admin panel (Filament, Laravel Nova, dll.), tidak ada paket PPDB/pembayaran, tidak ada paket CMS, tidak ada Three.js. Semua harus ditambahkan dengan pertimbangan fase implementasi.

---

## 3. Struktur Direktori Saat Ini

```
app/
├── Http/Controllers/          (kosong — hanya Controller base bawaan Laravel)
├── Models/
│   └── User.php               (Authenticatable, HasFactory, Notifiable)
└── Providers/
    └── AppServiceProvider.php (kosong)
bootstrap/
config/                        (app, auth, cache, database, filesystems, logging, mail, queue, services, session)
database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   └. 0001_01_01_000002_create_jobs_table.php
├── seeders/
│   └── DatabaseSeeder.php     (membuat 1 user test@example.com)
└── factories/
    └── UserFactory.php
public/
resources/
├── css/app.css                (Tailwind v4 import + font Instrument Sans)
├── js/app.js                  (kosong)
└── views/
    └── welcome.blade.php      (halaman default Laravel)
routes/
├── web.php                    (hanya Route::get('/', welcome))
└── console.php                (command inspire bawaan)
tests/
├── Feature/ExampleTest.php    (GET / -> 200)
├── Unit/ExampleTest.php       (assertTrue(true))
└── TestCase.php
vendor/
```

---

## 4. Migrasi yang Sudah Ada (DIGUNAKAN KEMBALI, TIDAK DIBUAT ULANG)

| Tabel | Status | Aksi |
|-------|--------|------|
| `users` | Ada | Digunakan kembali untuk akun internal (Admin, CS Admin, Guru, Staff, Siswa) |
| `password_reset_tokens` | Ada | Digunakan kembali (Laravel bawaan) |
| `sessions` | Ada | Digunakan kembali (database session) |
| `cache` | Ada | Digunakan kembali (Laravel bawaan) |
| `jobs` | Ada | Digunakan kembali (Laravel bawaan) |

**Tidak ada migration untuk:** roles, permissions, school_units, programs, content_items, media, ppdb_periods, ppdb_requirements, registrations, registration_documents, payments, payment_events, selection_decisions, notifications, audit_logs.

---

## 5. Model yang Sudah Ada (DIGUNAKAN KEMBALI)

| Model | Status | Aksi |
|-------|--------|------|
| `User` | Ada | Digunakan kembali. `#[Fillable(['name', 'email', 'password'])]`, `#[Hidden(['password', 'remember_token'])]`, cast `email_verified_at` dan `password` |

**Tidak ada model untuk:** Role, Permission, SchoolUnit, Program, ContentItem, Media, PpdbPeriod, PpdbRequirement, Registration, RegistrationDocument, Payment, PaymentEvent, SelectionDecision, Notification, AuditLog.

---

## 6. Routes yang Sudah Ada

| File | Route | Catatan |
|------|-------|---------|
| `routes/web.php` | `GET /` | Kembali `view('welcome')` — diganti oleh halaman publik DMR nanti |
| `routes/console.php` | `artisan inspire` | Bawaan Laravel |

**Tidak ada route untuk:** autentikasi internal, dashboard, CMS, PPDB, publikasi, admin, notifikasi.

---

## 7. Views yang Sudah Ada

| File | Catatan |
|------|---------|
| `resources/views/welcome.blade.php` | Halaman default Laravel (landing page generik) — akan diganti oleh layout publik DMR |

**Tidak ada view untuk:** layout website publik, halaman PPDB, dashboard internal, CMS, login internal.

---

## 8. Konfigurasi & Environment

| File | Status |
|------|--------|
| `.env` | Ada (lokal), di-.gitignore, **jangan tampilkan/salin secret** |
| `.env.example` | Ada, tanpa secret, perlu disesuaikan untuk proyek DMR |
| `config/app.php` | Standar Laravel, locale default `en` (harus diubah ke `id`, timezone `Asia/Jakarta`) |
| `config/auth.php` | Standar Laravel (guards web, provider users) |
| `config/database.php` | Standar Laravel (default sqlite, koneksi mysql tersedia) |
| `config/filesystems.php` | Standar Laravel (disk local, public) — perlu disk privat untuk dokumen PPDB |
| `config/services.php` | Kosong/standar, untuk integrasi pembayaran nantinya |
| `phpunit.xml` | In-memory SQLite, array cache/session/queue, BCRYPT_ROUNDS=4 |

---

## 9. Kompatibilitas dengan PRD (Revisi)

| Aspek PRD | Kondisi Repo | Gap yang Perlu Diisi |
|-----------|--------------|----------------------|
| Backend Laravel | Laravel 13 terpasang | Sesuai |
| PHP ^8.3 | Tersedia | Sesuai |
| MySQL/MariaDB via Laragon | Default SQLite di `.env`, MySQL tersedia di config | Ubah `.env` ke MySQL, buat database DMR |
| Blade + Tailwind + Vite | Sudah tersedia | Perlu design tokens & layout |
| Tailwind CSS v4 | Sudah terpasang | Perlu desain khas sekolah, tidak generik |
| Three.js | Belum terpasang | Tambah selektif di fase UI (dengan fallback) |
| PHPUnit | Sudah tersedia | Tambah test untuk authorization, CMS, PPDB, pembayaran |
| Laravel Pint | Sudah tersedia | Gunakan untuk formatting |
| `.env` tidak di-commit | Sudah di-.gitignore | Pertahankan |
| `roles/permissions` | Belum ada | Implementasi Fase 2 |
| CMS workflow (draft → publish) | Belum ada | Implementasi Fase 3 |
| PPDB (registrasi, dokumen, pembayaran, hasil) | Belum ada | Implementasi Fase 4 |
| Dashboard/notifikasi internal | Belum ada | Implementasi Fase 5 |

---

## 10. Risiko & Keputusan Terbuka (Revisi)

| # | Risiko/Keputusan | Status | Penanganan |
|---|------------------|--------|------------|
| 1 | Nama resmi yayasan/sekolah belum dikonfirmasi | Terbuka | Gunakan placeholder yang jelas di seeder dan view |
| 2 | Database produksi: MySQL/MariaDB vs SQLite default | Terbuka | `.env.example` dokumentasikan opsi MySQL; development pakai MySQL via Laragon |
| 3 | Provider pembayaran belum dipilih | Terbuka | Implementasi `PaymentProviderInterface` + `ManualPaymentProvider` sebagai default |
| 4 | Biaya pendaftaran belum ditetapkan | Terbuka | Kolom `registration_fee` nullable, dikonfigurasi Admin |
| 5 | Daftar dokumen wajib belum ditetapkan | Terbuka | `ppdb_requirements` dikonfigurasi Admin, tidak ada data bawaan |
| 6 | Role Petugas PPDB: tersendiri atau permission | Terbuka | Siapkan role `ppdb_officer` + permission, dokumentasikan opsi kedua |
| 7 | Logo/identitas visual belum tersedia | Terbuka | Design tokens dengan palet sementara, mudah diganti |
| 8 | Three.js performa dan fallback | Terbuka | Pasang selektif, ukur performa sebelum/sesudah, fallback statis |
| 9 | Field formulir PPDB final | Terbuka | Desain awal di ERD, perlu disetujui sekolah sebelum migration final |
| 10 | Pencegahan duplikat pendaftaran tanpa blokir calon siswa berbeda | Terbuka | Hindari unique constraint pada kombinasi identitas yang berisiko; gunakan application-level check + fingerprint hash yang diindex (bukan unique) |

---

## 11. Kesimpulan Audit

Repo adalah Laravel 13 skeleton bersih dengan dependensi inti yang sudah terpasang (Tailwind v4, Vite, PHPUnit, Pint). Belum ada fitur terkait PRD: tidak ada role/permission, CMS, PPDB, dashboard internal, atau halaman publik sekolah. Semua fitur harus dibangun dari nol sesuai fase yang direncanakan. Tidak ada file yang perlu ditimpa atau dihapus — hanya ditambah. Migrasi dan model bawaan Laravel (`users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`) akan digunakan kembali, tidak dibuat ulang.

**Langkah berikutnya:** Revisi `docs/FLOWCHART.md`, `docs/ERD.md`, dan `docs/IMPLEMENTATION_PLAN.md` berdasarkan review rancangan, lalu tampilkan untuk ditinjui sebelum migration final.