# Rencana Implementasi — Website Sekolah DMR + PPDB Online (Revisi)

**Dokumen acuan:** `PRD_Website_DMR_OpenCode.md` bab 2.2  
**Status:** Fase 0–2 selesai, Fase 3–5 berjalan, alur PPDB (Fase 4) sudah berfungsi  
**Aturan:** Implementasi bertahap per fase, uji setiap fase, jangan membuat migration final sebelum ERD (`docs/ERD.md`) disetujui pengguna.

---

## Ringkasan Fase (Revisi)

| Fase | Nama | Fokus | Status |
|------|------|-------|--------|
| 0 | Audit | Repo, dependensi, kondisi awal | ✅ Selesai (`docs/PROJECT_AUDIT.md`) |
| 1 | Perencanaan | Asumsi, keputusan, flowchart, ERD, rencana rute, role/permission, desain visual | ✅ Selesai (`docs/FLOWCHART.md`, `docs/ERD.md`, dokumen ini) |
| 2 | Fondasi | Konfigurasi, autentikasi, role/permission, layout, design tokens, data awal placeholder | ✅ Selesai |
| 3 | Website publik | Halaman publik dan CMS | 🔄 Berjalan (layout, design tokens, halaman informasi PPDB sudah ada; 16 halaman dan alur CMS belum) |
| 4 | PPDB | Formulir, akses status, dokumen, pembayaran, verifikasi, hasil | 🔄 Berjalan inti sudah berfungsi (service, controller, tampilan, pengujian) |
| 5 | Portal internal | Dashboard per role, notifikasi internal, audit log | 🔄 Berjalan (login, role/permission, dasbor dasar, modul PPDB internal sudah ada) |
| 6 | QA dan dokumentasi | Test, pemeriksaan akses, responsive review, README, dan laporan | 🔄 Berjalan (110 test otomatis lulus) |

---

## Aturan Dasar Implementasi (Revisi)

1. **Migration bawaan Laravel DIGUNAKAN KEMBALI, tidak dibuat ulang:** `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`.
2. **Tidak ada migration final sebelum ERD disetujui.** Dokumen ini adalah rencana saja.
3. **Setiap entitas harus punya alasan kebutuhan dari PRD.** Tidak membuat tabel karena terlihat berguna.
4. **Pencegahan duplikat pendaftaran di application level** (`identity_fingerprint` indexed, bukan unique constraint).
5. **Pembayaran mendukung percobaan ulang** (`payments.registration_id` tidak unique, `attempt_number`, `is_active`).
6. **Media publik dan privat terpisah** via `media.storage_visibility` dan `media.disk`.
7. **Actor recording** pada setiap transisi sensitif (CMS review, dokumen verifikasi, pembayaran manual, keputusan seleksi, reset kode akses).
8. **Status konsisten** di seluruh entitas (CMS, pendaftaran, dokumen, pembayaran, hasil seleksi).
9. **Data sekolah resmi tidak diarang.** Semua menggunakan placeholder yang diberi label jelas.

---

## Fase 2 — Fondasi (Autentikasi, Role/Permission, Layout, Design Tokens)

### Tujuan
Membangun fondasi keamanan dan tata letak yang akan digunakan semua fase berikutnya. Menggunakan migration `users` bawaan Laravel.

### File utama yang akan dibuat/diubah

| File | Keterangan |
|------|------------|
| `config/app.php` | Ubah locale ke `id`, timezone `Asia/Jakarta`, nama aplikasi placeholder |
| `.env.example` | Sesuaikan untuk proyek DMR (tanpa secret), dokumentasikan variabel yang perlu diisi sekolah |
| `app/Models/Role.php` | Model role |
| `app/Models/Permission.php` | Model permission |
| `database/migrations/xxxx_create_roles_table.php` | Tabel roles |
| `database/migrations/xxxx_create_permissions_table.php` | Tabel permissions |
| `database/migrations/xxxx_create_role_user_table.php` | Pivot role_user |
| `database/migrations/xxxx_create_permission_role_table.php` | Pivot permission_role |
| `database/migrations/xxxx_create_school_units_table.php` | Tabel school_units (SMP, SMA, SMK) |
| `database/migrations/xxxx_create_programs_table.php` | Tabel programs (terkait school_unit) |
| `app/Models/SchoolUnit.php` | Model school unit |
| `app/Models/Program.php` | Model program |
| `app/Policies/UserPolicy.php` | Policy untuk user management |
| `app/Policies/RolePolicy.php` | Policy untuk role/permission |
| `app/Http/Controllers/Auth/LoginController.php` | Login internal tunggal |
| `app/Http/Controllers/Auth/LogoutController.php` | Logout |
| `resources/views/auth/login.blade.php` | View login internal tunggal |
| `resources/views/layouts/app.blade.php` | Layout utama portal internal |
| `resources/views/layouts/public.blade.php` | Layout website publik |
| `resources/css/app.css` | Tambah design tokens (warna, tipografi, spacing, radius, shadow) |
| `resources/views/layouts/partials/navigation.blade.php` | Navigasi konsisten publik + internal |
| `routes/web.php` | Tambah route login, logout, dashboard, dan grup route per role |
| `database/seeders/DatabaseSeeder.php` | Seed data awal (role, permission, school_units, programs, user admin placeholder) |
| `database/factories/RoleFactory.php` | Factory role |
| `database/factories/PermissionFactory.php` | Factory permission |
| `database/factories/SchoolUnitFactory.php` | Factory school unit |
| `database/factories/ProgramFactory.php` | Factory program |

### Role dan Permission awal (dikonfigurasi database, tidak hardcode di route)

| Role | Permission contoh |
|------|-------------------|
| admin | `manage_users`, `manage_roles`, `manage_school_units`, `manage_programs`, `manage_periods`, `manage_requirements`, `manage_content`, `publish_content`, `verify_payment`, `set_selection_result`, `view_audit_log` |
| cs_admin | `view_assistance_queue`, `reset_access_code` |
| ppdb_officer | `verify_ppdb_documents`, `verify_ppdb_payment_manual` |
| teacher | `create_content_draft`, `submit_content_review` |
| staff | `create_content_draft`, `submit_content_review` |
| student | `view_student_dashboard` |

> Petugas PPDB bisa berupa role tersendiri `ppdb_officer` atau permission `verify_ppdb_documents` yang diberikan ke role lain. Keputusan final menunggu konfirmasi sekolah (PRD bab 4.6 dan 14.16).

### Design tokens (palet sementara, mudah diganti)
- Warna utama: placeholder (mis. hijau tua sebagai identitas pendidikan, akan diganti setelah logo resmi tersedia)
- Warna pendukung, status (success, warning, error, info), latar, teks, border
- Tipografi: judul, isi, caption, angka
- Spacing, radius, shadow, breakpoint (mobile/tablet/desktop)
- Fokus keyboard, hover, active, disabled, error, success, loading, empty state

### Uji coba Fase 2
- `composer test` (test yang ada masih lulus)
- Uji login internal dengan user admin dan non-admin
- Uji middleware: user tanpa role tidak bisa akses dashboard admin
- Uji pembuatan role dan permission via seeder

### Hasil akhir Fase 2
- Login internal tunggal berfungsi
- Role dan permission dapat dielola Admin
- Layout publik dan internal tersedia dengan design tokens
- Data awal (SMP, SMA, SMK; program Otomotif, PPLG sebagai placeholder) tersedia

---

## Fase 3 — Website Publik dan CMS

### Tujuan
Membangun 16 halaman publik + CMS dengan alur draft → submit → review → publish. Menggunakan model `users` bawaan untuk author/reviewer.

### File utama yang akan dibuat/diubah

| File | Keterangan |
|------|------------|
| `database/migrations/xxxx_create_content_items_table.php` | Tabel content_items |
| `database/migrations/xxxx_create_content_reviews_table.php` | Tabel content_reviews (dengan actor recording: reviewer_id, previous_status, new_status) |
| `database/migrations/xxxx_create_media_table.php` | Tabel media (dengan storage_visibility, disk) |
| `database/migrations/xxxx_create_filesystems_private_disk.php` | Konfigurasi disk privat untuk file sensitif (config filesystems) |
| `app/Models/ContentItem.php` | Model content item |
| `app/Models/ContentReview.php` | Model content review |
| `app/Models/Media.php` | Model media |
| `app/Policies/ContentItemPolicy.php` | Policy CMS |
| `app/Policies/MediaPolicy.php` | Policy media |
| `app/Http/Controllers/PublicController.php` | Controller halaman publik (home, profil, visi-misi, dll.) |
| `app/Http/Controllers/CmsController.php` | Controller CMS (create, edit, submit, review, publish, archive) |
| `app/Http/Controllers/MediaController.php` | Controller upload media |
| `app/Http/Requests/ContentItemRequest.php` | Request validation CMS |
| `app/Http/Requests/MediaRequest.php` | Request validation upload |
| `resources/views/public/*.blade.php` | 16 view halaman publik |
| `resources/views/cms/*.blade.php` | View CMS (daftar, form, review) |
| `resources/views/layouts/public.blade.php` | Layout publik (sudah dibuat Fase 2, diperbaiki di sini) |
| `routes/web.php` | Route publik (GET) dan route CMS (dengan middleware auth + policy) |
| `resources/js/app.js` | Inisialisasi Three.js selektif (opsional, dengan fallback) |
| `database/factories/ContentItemFactory.php` | Factory content item |
| `database/factories/MediaFactory.php` | Factory media |

### 16 Halaman Publik (hanya menampilkan `status = 'published'`)

1. Homepage — identitas, pilihan jenjang, program unggulan, sorotan berita, PPDB, kontak
2. Profil Sekolah — profil yayasan dan per jenjang
3. Visi & Misi — placeholder jika belum ada data resmi
4. Sambutan Kepala Sekolah — jika tersedia/diotorisasi
5. Guru & Staff — profil publik yang disetujui
6. Jurusan/Program — per jenjang (SMK: Otomotif, PPLG sebagai placeholder)
7. Fasilitas — daftar dan foto
8. Ekstrakurikuler — kegiatan yang dikonfirmasi
9. Prestasi — dengan tanggal/kategori, tidak mengarang statistik
10. Berita — daftar, detail, kategori, slug
11. Pengumuman — daftar, detail, status publikasi
12. Agenda/Kegiatan — tanggal, waktu, lokasi, status
13. Galeri & Video — foto/video yang disetujui
14. Informasi PPDB — periode aktif, langkah, persyaratan, biaya, metode, jadwal, FAQ, tombol daftar/status
15. Kontak — alamat, WhatsApp, email, jam layanan, peta (jika alamat resmi tersedia)
16. FAQ — kelola Admin

### Navigasi Jenjang
Pengunjung dapat memilih SMP, SMA, atau SMK. Konten difilter berdasarkan `school_unit_id` atau `null` (semua jenjang).

### Alur CMS (konsisten dengan FLOWCHART.md)
1. Guru/Staff membuat draft (`status = draft`)
2. Pengguna kirim untuk review (`status = submitted`)
3. Admin review (`status = approved` atau `needs_revision` dengan catatan)
4. Admin publikasikan (`status = published`, `published_at` diisi)
5. Konten published yang diubah kembali ke review ulang
6. Setiap transisi mencatat actor (author_id, reviewer_id, waktu, status sebelum/sesudah) di `content_reviews`

### Validasi Media (konsisten dengan ERD)
- Jenis file, ukuran, MIME type divalidasi server-side
- Nama file acak/aman (hash/uuid), bukan nama pengguna
- File disimpan di disk berdasarkan `storage_visibility` (public untuk foto konten, private untuk dokumen PPDB)
- Alt text/caption disimpan

### Uji coba Fase 3
- Uji 16 halaman publik dapat diakses (status 200)
- Uji konten draft tidak muncul di publik
- Uji filter jenjang SMP/SMA/SMK
- Uji Guru/Staff tidak bisa publish langsung
- Uji Admin bisa review, revisi, publish
- Uji upload file (jenis, ukuran, MIME yang valid/invalid)
- Uji responsive (mobile, tablet, desktop)
- Uji aksesibilitas (keyboard navigation, heading hierarchy, label form, alt text)

---

## Fase 4 — PPDB Online

### Tujuan
Membangun formulir pendaftaran, unggah dokumen, pembayaran hybrid, verifikasi, pemeriksaan status, dan hasil seleksi.

### File utama yang akan dibuat/diubah

| File | Keterangan |
|------|------------|
| `database/migrations/xxxx_create_ppdb_periods_table.php` | Tabel ppdb_periods (mandiri, cakupan unit lewat `ppdb_period_units`, registration_fee nullable) |
| `database/migrations/xxxx_create_ppdb_requirements_table.php` | Tabel ppdb_requirements (periode + school_unit + program opsional, sentinel `program_scope_id` untuk unique index) |
| `database/migrations/xxxx_create_registrations_table.php` | Tabel registrations (registration_number UQ, access_code_hash nullable, identity_fingerprint indexed NOT unique, access_code_revoked_at) |
| `database/migrations/xxxx_create_registration_documents_table.php` | Tabel registration_documents (media_id FK, reviewer_id actor, status) |
| `database/migrations/xxxx_create_payments_table.php` | Tabel payments (registration_id TIDAK unique, attempt_number, is_active, failure_reason, verified_by sebagai actor) |
| `database/migrations/xxxx_create_payment_events_table.php` | Tabel payment_events (unique gabungan `(provider, provider_event_id)` untuk idempotency) |
| `database/migrations/xxxx_create_selection_decisions_table.php` | Tabel selection_decisions (registration_id TIDAK unique, decided_by actor, previous_status, decision_status sebagai status sesudah) |
| `app/Models/PpdbPeriod.php` | Model periode PPDB |
| `app/Models/PpdbRequirement.php` | Model persyaratan dokumen |
| `app/Models/Registration.php` | Model pendaftaran |
| `app/Models/RegistrationDocument.php` | Model dokumen pendaftaran |
| `app/Models/Payment.php` | Model pembayaran |
| `app/Models/PaymentEvent.php` | Model event pembayaran |
| `app/Models/SelectionDecision.php` | Model keputusan hasil |
| `app/Policies/RegistrationPolicy.php` | Policy pendaftaran |
| `app/Policies/PaymentPolicy.php` | Policy pembayaran |
| `app/Policies/SelectionDecisionPolicy.php` | Policy keputusan hasil |
| `app/Services/PaymentProviderInterface.php` | Interface provider pembayaran (kontrak) |
| `app/Services/PaymentProviders/ManualPaymentProvider.php` | Implementasi manual (default) |
| `app/Http/Controllers/Public/PpdbInfoController.php` | Halaman informasi PPDB |
| `app/Http/Controllers/Public/RegistrationController.php` | Formulir pendaftaran |
| `app/Http/Controllers/Public/StatusController.php` | Pemeriksaan status (nomor + kode akses) |
| `app/Http/Controllers/Public/DocumentUploadController.php` | Unggah/re-upload dokumen |
| `app/Http/Controllers/Admin/PpdbPeriodController.php` | Kelola periode |
| `app/Http/Controllers/Admin/PpdbRequirementController.php` | Kelola persyaratan |
| `app/Http/Controllers/Admin/PpdbRegistrationController.php` | Kelola pendaftaran |
| `app/Http/Controllers/Admin/PpdbDocumentController.php` | Verifikasi dokumen |
| `app/Http/Controllers/Admin/PpdbPaymentController.php` | Konfirmasi pembayaran manual |
| `app/Http/Controllers/Admin/PpdbSelectionController.php` | Keputusan hasil |
| `app/Http/Middleware/ThrottleStatusCheck.php` | Rate limiting untuk status PPDB |
| `app/Http/Requests/RegistrationRequest.php` | Validasi formulir |
| `app/Http/Requests/StatusCheckRequest.php` | Validasi nomor + kode akses |
| `resources/views/public/ppdb/*.blade.php` | View PPDB (info, formulir, status, upload) |
| `resources/views/admin/ppdb/*.blade.php` | View admin PPDB |
| `routes/web.php` | Route PPDB publik dan admin |
| `database/factories/*` | Factory untuk semua model PPDB |
| `database/seeders/*` | Seeder periode dan persyaratan placeholder |

### Alur PPDB (konsisten dengan FLOWCHART.md)
1. Calon siswa pilih periode aktif, jenjang (SMP/SMA/SMK via covers_*), 1 program
2. Isi formulir + setujui privasi
3. Unggah dokumen
4. Server validasi + deteksi duplikat via `identity_fingerprint` (application level, tidak hard unique)
5. Buat nomor pendaftaran unik + kode akses hash
6. Tampilkan kode sekali, peringatan simpan aman
7. Pembayaran (otomatis via provider atau manual, mendukung percobaan ulang via `attempt_number`)
8. Verifikasi dokumen oleh petugas (`registration_documents.reviewer_id` actor)
9. Re-upload jika perlu revisi
10. Keputusan hasil oleh otoritas berwenang (`selection_decisions.decided_by` actor, `previous_status`/`new_status` history)
11. Calon siswa cek status via nomor + kode akses

### Status (konsisten di seluruh entitas)
Sumber tunggal ada di `docs/STATUS_MATRIX.md`; seluruh nilai di bawah
di-cast ke enum di `app/Enums/`.
- Konten: `draft`, `submitted`, `needs_revision`, `approved`, `published`, `archived`
- Dokumen: `uploaded`, `under_review`, `approved`, `needs_revision`, `rejected` (`missing` bukan baris, melainkan ketiadaan unggahan)
- Pembayaran: `pending_payment`, `pending_verification`, `confirmed`, `manual_review`, `failed`, `expired`
- Pendaftaran: `draft`, `submitted`, `payment_pending`, `documents_pending`, `documents_approved`, `documents_rejected`, `completed`, `expired`
- Periode: `draft`, `scheduled`, `open`, `closed`, `announced`
- Keputusan hasil: `pending`, `accepted`, `not_accepted`, `waitlisted`

### Keamanan PPDB
- Kode akses disimpan sebagai hash, tidak ada teks biasa
- `access_code_revoked_at` untuk pemulihan kode akses
- Rate limiting pada endpoint status PPDB
- Pesan error tidak membocorkan apakah nomor pendaftaran terdaftar
- Log tidak menyimpan kode akses mentah
- Pendaftar tidak bisa akses data pendaftar lain (policy berdasar registration + access_code)
- Dokumen diakses via endpoint otorisasi (`storage_visibility = 'private'`), bukan URL publik
- Webhook divalidasi signature, idempotent via `payment_events.provider_event_id`
- Status pembayaran terpisah dari status dokumen dan hasil seleksi

### Uji coba Fase 4
- Uji pendaftaran hanya bisa pilih 1 jenjang + 1 program
- Uji cegah pendaftaran gando di server (application-level fingerprint)
- Uji nomor pendaftaran unik
- Uji kode akses disimpan hash, ditampilkan sekali
- Uji pemeriksaan status dengan kredensial benar/salah
- Uji pendaftar tidak bisa akses data orang lain via URL manipulation
- Uji dokumen dikembalikan dengan catatan, bisa di-re-upload
- Uji pembayaran manual dengan audit log
- Uji webhook idempotent
- Uji hasil seleksi hanya role berwenang
- Uji pemulihan kode akses (batalkan lama, terbitkan baru, audit log)

---

## Fase 5 — Portal Internal (Dashboard, Notifikasi, Audit Log)

### Tujuan
Dashboard per role, notifikasi internal, dan audit log.

### File utama yang akan dibuat/diubah

| File | Keterangan |
|------|------------|
| `database/migrations/xxxx_create_notifications_table.php` | Tabel notifications |
| `database/migrations/xxxx_create_audit_logs_table.php` | Tabel audit_logs (actor recording, tanpa secret) |
| `app/Models/Notification.php` | Model notifikasi |
| `app/Models/AuditLog.php` | Model audit log |
| `app/Http/Controllers/Admin/DashboardController.php` | Dashboard Admin |
| `app/Http/Controllers/CsAdmin/DashboardController.php` | Dashboard CS Admin |
| `app/Http/Controllers/PpdbOfficer/DashboardController.php` | Dashboard Petugas PPDB |
| `app/Http/Controllers/Teacher/DashboardController.php` | Dashboard Guru |
| `app/Http/Controllers/Staff/DashboardController.php` | Dashboard Staff |
| `app/Http/Controllers/Student/DashboardController.php` | Dashboard Siswa |
| `app/Http/Controllers/NotificationController.php` | Kelola notifikasi |
| `app/Http/Controllers/AuditLogController.php` | Lihat audit log (hanya Admin) |
| `resources/views/dashboard/*.blade.php` | View dashboard per role |
| `resources/views/notifications/*.blade.php` | View notifikasi |
| `resources/views/audit-logs/*.blade.php` | View audit log |
| `routes/web.php` | Route dashboard, notifikasi, audit log |

### Dashboard per role
- **Admin:** ringkasan tugas review konten, periode PPDB, aktivitas yang perlu ditangani, pintasan manajemen
- **CS Admin:** antrean bantuan, pemulihan kode akses (tidak semua data pendaftar)
- **Petugas PPDB:** daftar dokumen menunggu review, kasus koreksi, pembayaran manual (jika punya permission)
- **Guru:** draft konten, status review, catatan revisi
- **Staff:** draft/konten dan tugas sesuai permission
- **Siswa:** Dashboard Siswa dengan informasi dasar dan fitur yang disetujui versi awal

### Notifikasi internal
Pemicu: draft dikirim, konten dikembalikan/dipublikasikan, dokumen PPDB baru/perlu koreksi, pembayaran manual/status berubah, pengingat periode PPDB (jika disetujui).
Setiap notifikasi: penerima, waktu, status dibaca, tautan internal aman, konteks. **Tanpa kode akses atau dokumen sensitif.**

### Audit log
Pencatat: perubahan role/permission, publikasi konten, review dokumen, pembayaran manual, perubahan hasil, reset kode akses. Tanpa password/kode akses/token mentah. `user_id` mencatat actor.

### Uji coba Fase 5
- Uji dashboard masing-masing role menampilkan data yang relevan
- Uji user tidak bisa akses dashboard di luar role-nya
- Uji notifikasi dibuat dan ditandai dibaca
- Uji audit log mencatat aktivitas sensitif dengan actor
- Uji Dashboard Siswa hanya menampilkan fitur yang disetujui

---

## Fase 6 — QA dan Dokumentasi

### Tujuan
Uji keseluruhan, dokumentasi, dan laporan hasil.

### Aktivitas
1. Jalankan `composer test` — semua test harus lulus
2. Uji authorization untuk setiap role (tidak boleh akses di luar kewenangan)
3. Uji workflow CMS (draft → publish tidak bisa bypass)
4. Uji pendaftaran, akses status, dokumen, pembayaran, hasil
5. Uji responsive (mobile, tablet, desktop)
6. Uji aksesibilitas (keyboard, kontras, label, alt text, fokus)
7. Uji Three.js (jika dipasang): fallback jika WebGL gagal, prefers-reduced-motion
8. Perbaikan bentuk: `vendor/bin/pint` dan `vendor/bin/pint --test`
9. Buat `README.md` dengan setup Windows/Laragon, konfigurasi DB, migration/seed, cara menjalankan, cara test
10. Dokumentasikan risiko dan keputusan terbuka

### File utama
| File | Keterangan |
|------|------------|
| `README.md` | Panduan instalasi dan pengujian (Windows/Laragon) |
| `tests/Feature/*` | Test fitur: authorization, CMS, PPDB, status, dokumen, pembayaran, hasil |
| `tests/Unit/*` | Test unit model dan service |
| `docs/` | FILE YANG SUDAH DIBUAT (audit, flowchart, ERD, rencana ini) |

### Test yang harus ditambahkan (daftar awal)
- `tests/Feature/Auth/LoginTest.php` — login internal, role-based redirect, rate limit
- `tests/Feature/Cms/ContentPublishTest.php` — Guru tidak bisa publish, Admin bisa, draft tidak publik
- `tests/Feature/Ppdb/RegistrationTest.php` — pendaftaran, cegah ganda, nomor unik, kode akses hash
- `tests/Feature/Ppdb/StatusTest.php` — pemeriksaan status, rate limit, error tidak bocorkan data
- `tests/Feature/Ppdb/DocumentTest.php` — upload, revisi, re-upload, akses privat
- `tests/Feature/Ppdb/PaymentTest.php` — pembayaran manual, audit log, webhook idempotent
- `tests/Feature/Ppdb/SelectionTest.php` — hasil seleksi hanya role berwenang
- `tests/Feature/Ppdb/AccessCodeRecoveryTest.php` — reset kode akses, batalkan lama, audit log

---

## Rencana Pengujian per Fase (Ringkasan)

| Fase | Perintah pengujian | Hasil yang diharapkan |
|------|-------------------|----------------------|
| 2 | `composer test` + uji manual login/role | Test esistente lulus, login berfungsi, role terpilah |
| 3 | `composer test` + uji 16 halaman + CMS | Halaman publik 200, draft tidak publik, CMS workflow berjalan |
| 4 | `composer test` + uji PPDB end-to-end | Pendaftaran, status, dokumen, pembayaran, hasil semua berjalan |
| 5 | `composer test` + uji dashboard/notifikasi | Dashboard per role, notifikasi, audit log berfungsi |
| 6 | `composer test` + `vendor/bin/pint --test` + responsive | Semua test lulus, kode diformat, dokumentasi lengkap |

---

## Risiko dan Keputusan Terbuka (Revisi)

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
| 10 | Pencegahan duplikat pendaftaran tanpa blokir calon siswa berbeda | Terbuka | Hindari unique constraint pada kombinasi identitas; gunakan `identity_fingerprint` indexed + application-level check |

---

## Koreksi terhadap Rencana Awal (hasil audit 19 Oktober 2026)

Rencana awal dan ERD berbeda pada beberapa hal. Yang dipakai sebagai
acuan sekarang adalah ERD revisi beserta `docs/STATUS_MATRIX.md`.

| Pernyataan rencana awal | Koreksi |
|---|---|
| `payment_events.provider_event_id` unique | Unique gabungan `(provider, provider_event_id)` |
| `selection_decisions.registration_id` unique | Tidak unique, tabel menyimpan riwayat keputusan |
| `ppdb_periods` memakai kolom `covers_smp/sma/smvk` | Cakupan unit memakai tabel penghubung `ppdb_period_units` |
| Kolom `registration_fingerprint` | Nama kolom `identity_fingerprint` |
| `selection_decisions` memakai `previous_status` dan `new_status` | `previous_status` dan `decision_status` sebagai status sesudah |
| `payments.manual_confirmed_by` | Kolom pelaku memakai `verified_by` |
| Status dokumen memuat `missing` | `missing` bukan baris, melainkan ketiadaan unggahan |
| Status pembayaran (`pending`, `paid`, `cancelled`, `requires_review`, `refunded`) | Mengikuti PRD bab 8.6: `pending_payment`, `pending_verification`, `confirmed`, `manual_review`, `failed`, `expired` |

Rincian temuan dan perbaikannya ada di `docs/PPDB_INTEGRATION_AUDIT.md`.

---

## Langkah Segera

1. **Fase 3 — website publik dan CMS:** lengkapi 15 halaman publik yang masih
   berupa placeholder (profil, visi-misi, guru/staf, fasilitas, kegiatan,
   prestasi, berita, pengumuman, agenda, galeri, kontak, FAQ) dan bangun alur
   CMS `draft → submit → review → publish` memakai model `content_items` dan
   `content_revisions` yang sudah ada.
2. **Fase 5 — portal internal:** dasbor per role yang memakai data nyata,
   halaman notifikasi internal, dan penampil audit log.
3. **Integrasi pembayaran otomatis:** setelah provider resmi ditetapkan, buat
   adapter `PaymentProviderInterface` baru dengan validasi signature webhook
   dan tipe event provider. Kontrak, idempotency, dan alur manual sudah siap.
4. Jalankan `composer test` dan `vendor/bin/pint --test` setelah setiap
   perubahan.
5. Dokumentasikan file yang dibuat/diubah di akhir setiap fase.

**Migration final untuk tabel baru tetap memerlukan persetujuan ERD. Penyesuaian skema yang sudah disetujui (audit integrasi PPDB) sudah diterapkan pada migration `2026_10_19_000023` dan `000024`.**

---

## Implementasi Fase 4 — PPDB (terakhir diperbarui 19 Oktober 2026)

Inti alur PPDB sudah berfungsi dan diuji. Ringkasan file yang dibuat:

### Service layer (`app/Services/`)

| File | Tanggung jawab |
|---|---|
| `AuditLogger.php` | Mencatat jejak aktivitas sensitif dengan penyaringan nilai rahasia memakai `AuditLog::sanitize()` |
| `Ppdb/RegistrationService.php` | Menerbitkan nomor pendaftaran dan kode akses, mendeteksi pendaftaran ganda lewat fingerprint HMAC, memvalidasi periode, unit, dan program |
| `Ppdb/RegistrationResult.php` | Pembungkus hasil pendaftaran; kode akses hanya tersedia sekali |
| `Ppdb/AccessCodeService.php` | Verifikasi kode, pemulihan kode (membatalkan kode lama), dan pencatatan percobaan gagal |
| `Ppdb/DocumentService.php` | Unggah berkas ke disk privat, versi dokumen, daftar dokumen yang belum terpenuhi, unduhan terotorisasi |
| `Ppdb/DocumentReviewService.php` | Setujui, kembalikan dengan catatan koreksi, tolak, dan sinkronisasi status pendaftaran |
| `Ppdb/PaymentService.php` | Percobaan pembayaran, konfirmasi manual, penandaan gagal, dan penerimaan webhook idempoten |
| `Ppdb/SelectionService.php` | Penetapan hasil seleksi beserta jejak status sebelumnya |
| `Payment/PaymentProviderInterface.php` | Kontrak adapter provider pembayaran |
| `Payment/ManualPaymentProvider.php` | Implementasi manual bawaan sampai provider resmi ditetapkan |
| `Payment/PaymentEventPayload.php` | Data peristiwa pembayaran hasil baca webhook |

### Controller, request, policy, dan middleware

- `app/Http/Controllers/Public/`: `PpdbInfoController`, `RegistrationController`,
  `StatusController`, `RegistrationDocumentController`.
- `app/Http/Controllers/Admin/`: `PpdbPeriodController`, `PpdbRequirementController`,
  `PpdbRegistrationController`, `PpdbDocumentController`, `PpdbPaymentController`,
  `PpdbSelectionController`.
- `app/Http/Requests/`: `RegistrationRequest`, `StatusCheckRequest`,
  `DocumentUploadRequest`.
- `app/Policies/`: `RegistrationPolicy`, `RegistrationDocumentPolicy`,
  `PaymentPolicy`, `SelectionDecisionPolicy`.
- `app/Http/Middleware/EnsureRegistrationAccess.php` menjaga halaman status
  dan unggah dokumen lewat sesi yang sudah diverifikasi.
- `app/Exceptions/Ppdb/`: `RegistrationException`, `DocumentUploadException`,
  `PaymentException`.

### Konfigurasi

- Disk privat `ppdb` pada `config/filesystems.php` (root `storage/app/ppdb`,
  di luar `public/`).
- `config/dmr.php`: `privacy_notice_version`, `fingerprint_key`,
  `registration_number_prefix`, `private_disk`, `max_upload_kb`.
- Rate limiter `ppdb-status` dan `ppdb-registration` pada `AppServiceProvider`.

### Tampilan

- Publik: `public/ppdb/info`, `register`, `registered`, `status-form`, `status`.
- Internal: `admin/ppdb/periods`, `admin/ppdb/requirements`,
  `admin/ppdb/registrations`, `admin/ppdb/documents`, `admin/ppdb/payments`.
- Komponen `components/status-badge.blade.php` untuk badge status konsisten.

### Pengujian

`tests/Feature/Ppdb/`: `RegistrationTest`, `StatusTest`, `DocumentTest`,
`PaymentTest`, `SelectionTest`, `AccessCodeRecoveryTest`, `AdminPortalTest`,
dengan basis skenario `PpdbScenario.php`.

**Total test proyek: 110 lulus, 353 assertion.**

### Yang masih menjadi PR dalam Fase 4

1. Provider pembayaran otomatis (menunggu keputusan sekolah).
2. Notifikasi internal untuk pemicu PPDB (Fase 5).
3. Penampil audit log di portal internal (Fase 5).
4. Pengingat periode PPDB yang mendekati tanggal (menunggu persetujuan).