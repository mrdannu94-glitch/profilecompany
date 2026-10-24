# Website Profil Sekolah DMR + PPDB Online

Website terpadu untuk profil sekolah swasta (SMP, SMA, SMK) di bawah satu
yayasan, dilengkapi proses PPDB online dan portal internal untuk Admin,
CS Admin, Petugas PPDB, Guru, Staff, dan Siswa.

> **Penting:** nama resmi yayasan/sekolah, logo, visi-misi, biaya PPDB,
> daftar dokumen wajib, dan kebijakan pembayaran **belum dikonfirmasi**.
> Seluruh data tersebut memakai placeholder yang diberi label jelas di
> `config/dmr.php`, seeder, dan tampilan. Jangan mengarang data resmi.

---

## Kebutuhan Sistem

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | 8.3+ | ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| Composer | 2.x | |
| Node.js | 20+ | hanya untuk>Vite build |
| MySQL / MariaDB | 8.0 / 10.6+ | pengembangan memakai MySQL di Laragon |

Lingkungan pengembangan pada proyek ini: Windows, Laragon, PowerShell.

---

## Instalasi (Windows / Laragon)

```powershell
composer setup
```

Perintah tersebut menjalankan: install dependensi PHP, menyalin `.env`,
membuat app key, menjalankan migration, `npm install --ignore-scripts`, dan
build aset frontend.

Instalasi manual:

```powershell
composer install
copy .env.example .env
php artisan key:generate
# sesuaikan DB_* pada .env, lalu:
php artisan migrate
php artisan db:seed
npm install --ignore-scripts
npm run build
```

---

## Konfigurasi Database

`.env` dipakai untuk-development, MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dmr
DB_USERNAME=root
DB_PASSWORD=
```

Buat basis data kosong terlebih dahulu, lalu jalankan `php artisan migrate`
dan `php artisan db:seed`. Seeder membuat role, permission, unit sekolah
placeholder, dan satu akun administrator.

Variabel lain yang perlu disiapkan:

| Variabel | Fungsi |
|---|---|
| `DMR_SCHOOL_NAME` | Nama sekolah yang tampil pada judul dan email |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` | Akun administrator awal hasil seeder |
| `PPDB_ACCESS_CODE_TTL_DAYS` | Masa berlaku kode akses pendaftaran |
| `PPDB_STATUS_RATE_LIMIT` | Batas pemeriksaan status PPDB per menit |
| `PPDB_REGISTRATION_PREFIX` | Awalan nomor pendaftaran, contoh `PPDB-2026-0001` |
| `PPDB_PRIVATE_DISK` | Disk privat penyimpanan dokumen PPDB (bawaan `ppdb`) |
| `PPDB_MAX_UPLOAD_KB` | Batas ukuran unggahan dokumen di luar persyaratan |
| `DMR_FINGERPRINT_KEY` | Kunci HMAC fingerprint identitas calon siswa |
| `DMR_PRIVACY_NOTICE_VERSION` | Versi pernyataan privasi yang dicatat saat persetujuan |
| `AUTH_LOGIN_MAX_ATTEMPTS`, `AUTH_LOGIN_LOCKOUT_MINUTES` | Pembatasan percobaan login |

Seluruh nilai placeholder di atas dikumpulkan di `config/dmr.php` agar mudah
diganti begitu data resmi sekolah tersedia.

> `DMR_FINGERPRINT_KEY` sebaiknya ditetapkan sekali dan tidak diubah setelah
> pendaftaran mulai masuk, karena perubahan kunci membuat perbandingan
> fingerprint identitas tidak lagi kecocokan. Bila dikosongkan, application
> key dipakai sebagai cadangan.

---

## Menjalankan Aplikasi

```powershell
composer dev          # php artisan dev: server + queue listener + Vite
```

atau terpisah:

```powershell
php artisan serve
npm run dev            # Vite dev server
```

Alamat aplikasi: `http://127.0.0.1:8000`. Halaman login internal tersedia di
`/login`.

---

## Alur PPDB Online

| Tahap | Rute | Penanggung jawab |
|---|---|---|
| Informasi PPDB | `/ppdb` | Pengunjung |
| Formulir pendaftaran | `/ppdb/daftar` | Calon siswa |
| Nomor pendaftaran + kode akses | tampil sekali setelah pendaftaran | Calon siswa |
| Pemeriksaan status | `/ppdb/status` | Calon siswa |
| Unggah dan unduh dokumen | `/ppdb/status/dokumen` | Calon siswa |
| Periode dan persyaratan | `/portal/ppdb/periode`, `/portal/ppdb/persyaratan` | Admin |
| Antrean pemeriksaan dokumen | `/portal/ppdb/dokumen` | Petugas PPDB |
| Verifikasi pembayaran manual | `/portal/ppdb/pembayaran` | Petugas PPDB |
| Detail pendaftaran dan hasil seleksi | `/portal/ppdb/pendaftaran` | Petugas PPDB, Admin |

Catatan penting:

- Calon siswa **tidak** memakai akun. Akses status memakai nomor pendaftaran
  dan kode akses rahasia yang hanya ditampilkan sekali.
- Kode akses disimpan sebagai hash, tidak pernah ditampilkan kembali, dan
  dapat dipulihkan oleh CS Admin lewat `reset_access_code`. Kode lama
  dibatalkan saat kode baru diterbitkan.
- Dokumen PPDB disimpan pada disk privat di luar `public/`. Unduhan hanya
  lewat endpoint yang memeriksa sesi calon siswa atau permission petugas.
- Unggahan bukti pembayaran **tidak** otomatis dianggap lunas. Konfirmasi
  hanya dilakukan petugas berwenang dan selalu menghasilkan audit log.
- Status pembayaran, dokumen, dan hasil seleksi dicatat terpisah.

---

## Menjalankan Test

```powershell
composer test          # membersihkan cache konfigurasi lalu menjalankan PHPUnit
```

Pengujian memakai SQLite in-memory, array cache/session/queue, sehingga tidak
memerlukan layanan tambahan. Format kode:

```powershell
vendor/bin/pint        # format
vendor/bin/pint --test # hanya pemeriksaan
```

---

## Struktur Proyek

```
app/
  Enums/          kosakata status kanonik (PPDB, media)
  Exceptions/     pengecualian domain PPDB (pendaftaran, dokumen, pembayaran)
  Http/
    Controllers/  Auth (login internal), Public (halaman publik), Admin (portal PPDB), DashboardController
    Middleware/   EnsureRole, EnsurePermission, EnsureRegistrationAccess
    Requests/     validasi formulir pendaftaran, status, dan unggah dokumen
  Models/         model Eloquent untuk seluruh entitas
  Policies/       UserPolicy, RolePolicy, PermissionPolicy, policy PPDB
  Services/
    AuditLogger.php           pencatatan jejak aktivitas sensitif
    Payment/                  kontrak provider pembayaran + implementasi manual
    Ppdb/                     service layer pendaftaran, kode akses, dokumen,
                               pembayaran, dan hasil seleksi
database/
  migrations/     skema (termasuk 8 tabel PPDB)
  seeders/        role, permission, unit sekolah, admin placeholder
  factories/      factory pengujian
docs/             audit, flowchart, ERD, matriks status, rencana implementasi
resources/
  css/app.css     design tokens (Tailwind v4)
  views/          layouts publik, layouts portal, halaman publik, dasbor,
                  halaman PPDB publik, dan portal PPDB internal
routes/web.php    route publik, autentikasi, PPDB, dan portal internal
tests/            Feature (auth, seeder, PPDB) dan Unit (role/permission)
```

---

## Dokumentasi

| Dokumen | Isi |
|---|---|
| `PRD_Website_DMR_OpenCode.md` | Persyaratan produk |
| `docs/PROJECT_AUDIT.md` | Audit kondisi awal repo |
| `docs/FLOWCHART.md` | Alur CMS, PPDB, pembayaran, dan login |
| `docs/ERD.md` | ERD Mermaid, atribut, constraint, dan alasan entitas |
| `docs/STATUS_MATRIX.md` | Kosakata status kanonik dan transisinya |
| `docs/PPDB_INTEGRATION_AUDIT.md` | Hasil audit integrasi 8 model PPDB dan perbaikannya |
| `docs/IMPLEMENTATION_PLAN.md` | Rencana per fase, status(real), dan risiko terbuka |

---

## Status Saat Ini

- **Fase 0–1 (audit dan perencanaan):** selesai.
- **Fase 2 (fondasi):** selesai. Login internal tunggal, role/permission dari
  database, middleware `role` dan `permission`, layout publik dan portal,
  design tokens, seeder, dan factory sudah tersedia.
- **Fase 3 (website publik dan CMS):** berjalan. Layout dan route placeholder
  tersedia; isi 16 halaman publik dan alur CMS belum dibangun. Halaman
  informasi PPDB sudah memakai data nyata dari periode yang dikonfigurasi.
- **Fase 4 (PPDB):** berjalan. Skema, model, enum, constraint integritas,
  service layer, controller, tampilan, dan pengujian sudah tersedia.
  Yang belum ada: provider pembayaran otomatis (sekarang manual),
  pengingat periode, dan integrasi-notifikasi internal.
- **Fase 5 (portal internal):** berjalan. Dasbor dasar per role tersedia;
  modul PPDB internal sudah aktif, notifikasi dan audit log viewer belum.
- **Fase 6 (QA dan dokumentasi):** berjalan. 110 test otomatis lulus.

Status pengujian terakhir: `110 test, 110 lulus, 353 assertion`
(`composer test`). Build frontend: `npm run build` berhasil.

---

## Yang Masih Menunggu Keputusan Sekolah

1. Nama resmi yayasan dan setiap jenjang.
2. Logo, palet warna, dan aset visual.
3. Visi, misi, dan sambutan kepala sekolah.
4. Alamat, WhatsApp, email, jam layanan, dan media sosial.
5. Daftar guru/staf yang boleh ditampilkan.
6. Deskripsi resmi program, fasilitas, kegiatan, dan prestasi.
7. Field formulir PPDB final.
8. Dokumen wajib per jenjang dan periode.
9. Nominal biaya pendaftaran, provider, metode, dan kebijakan pembayaran.
10. Jadwal periode PPDB dan tanggal pengumuman.
11. Otoritas penetapan hasil seleksi dan definisi status seleksi.
12. Retensi data calon siswa dan dokumen.
13. Prosedur verifikasi pemulihan kode akses.
14. Apakah Petugas PPDB memakai role tersendiri atau permission khusus.

Sampai data ini tersedia, sistem memakai placeholder, konfigurasi di
`config/dmr.php`, dan panel admin.
