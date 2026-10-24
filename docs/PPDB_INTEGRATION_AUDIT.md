# Audit Integrasi 8 Model PPDB

**Tanggal audit:** 19 Oktober 2026
**Ruang lingkup:** `PpdbPeriod`, `PpdbPeriodUnit`, `PpdbRequirement`,
`Registration`, `RegistrationDocument`, `Payment`, `PaymentEvent`,
`SelectionDecision` beserta migration `2026_10_19_000013` … `000020`.
**Acuan:** `PRD_Website_DMR_OpenCode.md`, `docs/ERD.md`, `docs/FLOWCHART.md`,
`docs/IMPLEMENTATION_PLAN.md`.

Dokumen ini mencatat hasil audit integrasi, keputusan yang diambil, dan
perubahan yang sudah diterapkan. Hanya temuan yang sudah diverifikasi lewat
test atau query database yang ditandai **Diterapkan**.

---

## 1. Ringkasan

| Kategori | Jumlah |
|---|---|
| Temuan P1 (menghambat atau berisiko keamanan) | 4 |
| Temuan P2 (penting untuk integritas data) | 15 |
| Temuan P3 (minor dan konsistensi) | 11 |
| Diterapkan pada sesi ini | 14 |
| Ditunda ke fase berikutnya | 8 |
| Dipertahankan dengan catatan | 4 |

Pengujian pada sesi ini: **110 test, 110 lulus** pada SQLite in-memory, dan
migration baru berhasil dijalankan pada MySQL lingkungan pengembangan.

---

## 2. Temuan P1

### P1-1 Kolom server dapat diisi lewat mass assignment (Diterapkan)

`Registration::$fillable` memuat `registration_number`, `access_code_hash`,
`identity_fingerprint`, `status`, dan `submitted_at`. Dengan `fill()` atau
`create()` terhadap data permintaan, pendaftar dapat menentukan sendiri
nomor pendaftaran, memilih hash kode akses, dan menyetel status menjadi
`completed`.

**Perbaikan:** seluruh kolom tersebut dikeluarkan dari `$fillable`. Penerbitan
nomor pendaftaran, kode akses, fingerprint, status, dan waktu pengiriman
sekarang hanya bisa dilakukan server-side lewat `forceFill()`. Kolom
`ppdb_periods.created_by` diperlakukan sama. Status `completed` hanya dapat
dicapai melalui alur verifikasi dokumen dan keputusan hasil.

**Verifikasi:** `tests/Feature/Ppdb/PpdbIntegrityTest.php`
(`test_kolom_terbitkan_server_tidak_bisa_diisi_mass_assignment`,
`test_nomor_pendaftaran_wajib_diterbitkan_server`).

### P1-2 Rantai cascade menghapus riwayat PPDB (Diterapkan)

`users → ppdb_periods → registrations → registration_documents / payments /
selection_decisions` memakai `ON DELETE CASCADE`. Menghapus satu akun
pengguna dapat menghapus seluruh riwayat pendaftaran, pembayaran, dan
keputusan seleksi. ERD 4.16 melarang hal ini.

**Perbaikan:** migration `2026_10_19_000024_restrict_ppdb_foreign_keys`
mengubah 14 foreign key data transaksional menjadi `ON DELETE RESTRICT`.
Relasi yang tidak mengubah makna data tetap `SET NULL`. Pivot
`ppdb_period_units` tetap `CASCADE`.

**Verifikasi:** `information_schema.REFERENTIAL_CONSTRAINTS` pada MySQL
menghasilkan `RESTRICT` untuk seluruh relasi tersebut.

### P1-3 Tidak ada unique constraint untuk scope persyaratan (Diterapkan)

`ppdb_requirements` tidak memiliki unique index sehingga kode persyaratan
yang sama dapat diduplikasi pada periode, unit, dan program yang sama.

**Perbaikan:** kolom sentinel `program_scope_id` (nilai `0` berarti
persyaratan umum) ditambahkan dan dipakai pada constraint
`unique (ppdb_period_id, school_unit_id, program_scope_id, requirement_code)`.
Kolom sentinel dijaga model pada hook `saving`.

**Catatan teknis:** kolom `STORED` hasil generate tidak bisa ditambahkan ke
tabel yang sudah ada pada SQLite (`cannot add a STORED column`), dan unique
index dengan `NULL` tidak membedakan baris dengan `program_id IS NULL`.
Karena itu sentinel dipakai agar constraint berlaku sama di MySQL dan
SQLite.

**Verifikasi:** `test_persyaratan_dengan_scope_sama_ditolak` dan
`test_persyaratan_umum_dan_khusus_program_dapat_berdampingan`.

### P1-4 `access_code_hash` tidak bisa kosong (Diterapkan)

Kolom `access_code_hash` dibuat `NOT NULL`, padahal ERD 4.16 menyebutnya
"wajib setelah akses diaktifkan" dan status `draft` belum memiliki kode
akses.

**Perbaikan:** kolom menjadi nullable pada migration
`2026_10_19_000023_harden_ppdb_data_integrity`.

---

## 3. Temuan P2

| # | Temuan | Perlakuan |
|---|---|---|
| P2-1 | `PpdbPeriodUnit` memakai kunci primer semu sehingga `getKey()`, `is()`, dan `find()` tidak membedakan baris | **Diterapkan.** `getKey()` mengembalikan kunci gabungan `periode:unit`, `setKeysForSaveQuery()` memakai nilai asli kedua kolom, dan helper `findByPair()` serta `link()` ditambahkan |
| P2-2 | Tidak ada kosakata status terpusat; semua kolom status berupa `string` bebas | **Diterapkan.** Delapan enum baru di `app/Enums/` dan seluruh kolom status di-cast ke enum. Lihat `docs/STATUS_MATRIX.md` |
| P2-3 | `selection_decisions.is_current` tidak dijamin tunggal per pendaftaran | **Diterapkan.** Sentinel `current_registration_id` dengan unique index, dijaga hook `saving` |
| P2-4 | `registrations.identity_fingerprint` tidak diindeks sehingga deteksi duplikasi memindai seluruh tabel | **Diterapkan.** Index `reg_identity_fp_idx (ppdb_period_id, identity_fingerprint)` |
| P2-5 | Sembilan index pendukung query yang disarankan ERD belum ada | **Diterapkan.** Semua index dibuat pada migration `000023` |
| P2-6 | Model `AuditLog` dan `Notification` kosong sehingga `create()` melempar `MassAssignmentException` | **Diterapkan.** Keduanya kini punya `$fillable`, cast, relasi, dan helper penyaring rahasia |
| P2-7 | `audit_logs.entity_id` `NOT NULL` sehingga aktivitas sistem tidak bisa dicatat | **Diterapkan.** Kolom menjadi nullable |
| P2-8 | Tidak ada rate limiting, policy, maupun endpoint pemulihan kode akses | **Ditunda.** Middleware `role` dan `permission` sudah ada; rate limit status PPDB dan pemulihan kode mengikuti fase PPDB |
| P2-9 | Tidak ada disk privat khusus PPDB dan `storage_visibility` tidak dibatasi | **Sebagian.** `storage_visibility` kini memakai enum `StorageVisibility`; disk privat khusus PPDB mengikuti fase PPDB |
| P2-10 | `registration_documents` cascade ke `ppdb_requirements` dan `media` menghapus versi dokumen | **Diterapkan.** Kedua foreign key menjadi `RESTRICT` |
| P2-11 | `program_id` memakai `SET NULL` sehingga menghapus program menurunkan secara diam-diam cakupan persyaratan dan pendaftaran | **Diterapkan.** Kedua foreign key menjadi `RESTRICT`; program dinonaktifkan lewat kolom `is_active` |
| P2-12 | `selection_decisions` tidak menyimpan status sebelum dan sesudah seperti diwajibkan PRD 8.7 | **Diterapkan.** Kolom `previous_status` ditambahkan; `decision_status` berperan sebagai status sesudah |
| P2-13 | `payments` tidak dapat menyatakan percobaan ulang (`attempt_number`, `is_active`) | **Diterapkan.** Kedua kolom, kolom `failure_reason`, dan sentinel `active_registration_id` ditambahkan |
| P2-14 | `registration_documents.status` dapat berisi `missing` yang dilarang ERD 4.17 | **Diterapkan.** Enum `DocumentStatus` tidak memuat `missing` sehingga nilai tersebut ditolak |
| P2-15 | Beberapa relasi invers belum ada (`Media::registrationDocuments()`, `SchoolUnit::requirements()`, `Program::registrations()`, dan lainnya) | **Diterapkan.** Relasi invers dilengkapi |

---

## 4. Temuan P3

| # | Temuan | Perlakuan |
|---|---|---|
| P3-1 | `ppdb_requirements.accepted_mime_types` berupa CSV sementara `payment_policy` berupa JSON | **Diterapkan.** Kolom diubah menjadi JSON dengan cast `array`; data CSV lama dikonversi saat migration |
| P3-2 | `payment_events` dan `selection_decisions` memiliki `updated_at` yang tidak ada di ERD | **Dipertahankan.** Kolom timestamp otomatis Laravel tidak berisiko dan memudahkan penelusuran |
| P3-3 | `PpdbPeriod` tidak memiliki kosakata `status` | **Diterapkan.** Enum `PpdbPeriodStatus` |
| P3-4 | `payments.verification_method` `NOT NULL` tanpa default | **Diterapkan** sebagai bagian enum `VerificationMethod`; nilai wajib diisi saat membuat percobaan |
| P3-5 | `PpdbPeriod::schoolUnits()->attach()` menghasilkan galat constraint mentah saat data ganda | **Diterapkan.** Helper `PpdbPeriodUnit::link()` memakai `firstOrCreate` |
| P3-6 | `Registration` masih dapat diserialisasi menjadi data pribadi | **Sebagian.** Atribut sensitif disembunyikan lewat `#[Hidden]`; daftar kolom allow-list untuk halaman status mengikuti fase PPDB |
| P3-7 | `provider_reference`, `verification_note`, dan `payload_reference` dapat menyimpan token | **Ditunda.** Validasi format dan larangan menyimpan payload mentah mengikuti implementasi provider |
| P3-8 | `ppdb_periods.payment_policy` dapat memuat secret provider dalam JSON | **Ditunda.** Pembatasan kunci mengikuti keputusan provider |
| P3-9 | Fixture test memakai `hash('sha256')` untuk kode akses | **Sebagian.** Verifikasi produksi memakai `Hash::check()`; fixture diperbarui pada implementasi service |
| P3-10 | `content_revision_media` hanya punya `created_at`, tetapi kedua sisi relasi memakai `withTimestamps()` | **Diterapkan.** `withTimestamps()` dihapus dari `Media::revisions()` dan `ContentRevision::media()` |
| P3-11 | Rencana implementasi dan ERD berbeda pada empat hal | **Diterapkan.** `docs/IMPLEMENTATION_PLAN.md` dikoreksi; lihat bagian 6 |

---

## 5. Keputusan yang diambil

1. **Status pembayaran** mengikuti PRD bab 8.6 (`pending_payment`,
   `pending_verification`, `confirmed`, `manual_review`, `failed`, `expired`)
   dan menggantikan daftar pada ERD lama. Sumber tunggal ada di
   `docs/STATUS_MATRIX.md`.
2. **`event_type` pembayaran** dibiarkan berupa string bebas karena nama
   event berbeda antar provider. Yang dikendalikan adalah
   `processing_status`.
3. **Sentinel, bukan generated column,** untuk constraint yang melibatkan
   `NULL`, karena SQLite tidak mendukung penambahan kolom `STORED` pada
   tabel yang sudah ada.
4. **Aturan `ON DELETE` hanya diterapkan pada MySQL dan MariaDB.** SQLite
   menetapkan aturan tersebut saat tabel dibuat sehingga tidak dapat diubah
   tanpa build ulang tabel. Lingkup pengujian otomatis memakai SQLite.
5. **Kolom terbit server tidak bisa diisi lewat mass assignment.** Semua
   service layer PPDB berikutnya harus memakai `forceFill()`.

---

## 6. Koreksi terhadap `docs/IMPLEMENTATION_PLAN.md`

| Pernyataan lama | Koreksi |
|---|---|
| `payment_events.provider_event_id` unique | Sebenarnya unique gabungan `(provider, provider_event_id)` |
| `selection_decisions.registration_id` unique | Salah: sengaja tidak unique untuk menyimpan riwayat |
| `ppdb_periods` memakai kolom `covers_smp/sma/smvk` | Salah: memakai tabel penghubung `ppdb_period_units` |
| Kolom `registration_fingerprint` | Nama sebenarnya `identity_fingerprint` |
| `selection_decisions` memakai `previous_status` dan `new_status` | Diimplementasikan sebagai `previous_status` dan `decision_status` sebagai status sesudah |
| `payments.manual_confirmed_by` | Digantikan `verified_by` yang sudah ada sebagai kolom pelaku |

---

## 7. Yang belum selesai

- Migration `000024` tidak mengubah aturan `ON DELETE` pada SQLite, sehingga
  pengujian otomatis belum memverifikasi perilaku `RESTRICT`.
- ~~Belum ada service layer PPDB~~ **Selesai pada 19 Oktober 2026.**
  Service layer pendaftaran, kode akses, dokumen, pembayaran, dan hasil
  seleksi sudah dibuat; rincian file ada di `docs/IMPLEMENTATION_PLAN.md`.
- ~~Belum ada disk privat khusus PPDB maupun endpoint unduhan dokumen yang
  memeriksa otorisasi.~~ **Selesai.** Disk privat `ppdb` dipakai untuk berkas
  PPDB, dan pengunduhan hanya dapat dilakukan lewat sesi calon siswa
  terverifikasi atau permission `verify_ppdb_documents`.
- ~~Belum ada audit logger service yang menyaring rahasia~~ **Selesai.**
  `App\Services\AuditLogger` menyaring nilai sebelum menulis ke `audit_logs`.
- ~~Belum ada rate limiting untuk pemeriksaan status PPDB dan pemulihan
  kode.~~ **Selesai untuk pemeriksaan status.** Rate limiter `ppdb-status`
  membatasi percobaan per alamat jaringan dan per nomor pendaftaran.
  Pemulihan kode dibatasi permission `reset_access_code` dan tercatat pada
  audit log.
- Belum ada provider pembayaran otomatis. Kontrak
  `App\Services\Payment\PaymentProviderInterface` dan alur idempoten sudah
  disiapkan, tetapi implementasi bawaan masih `ManualPaymentProvider`
  sampai sekolah menetapkan provider resmi.
- Belum ada penampil audit log dan notifikasi internal di portal (Fase 5).
