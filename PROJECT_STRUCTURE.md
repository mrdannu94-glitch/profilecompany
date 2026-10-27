# Struktur dan Audit Proyek DMR

> Audit statis dan verifikasi terbatas. Hanya dokumen ini yang dibuat; tidak ada file aplikasi atau database yang diubah oleh audit.

## 1. Informasi Proyek

| Item | Hasil terverifikasi |
|---|---|
| Nama kerja | Website Profil Company Sekolah DMR + PPDB Online |
| Root repository | `C:\laragon\www\laravel` |
| Git | Branch `main`, tracking `origin/main` |
| Framework | Laravel 13.32.0 |
| PHP CLI | PHP 8.3.33 |
| Backend dependency range | `laravel/framework ^13.17`, `php ^8.3` |
| Frontend | Blade, Vite 8.3.4, Tailwind CSS 4.3.3 |
| Node / npm | Node 24.22.0 / npm 11.19.1 |
| Database konfigurasi | SQLite adalah default di `config/database.php` dan `.env.example`; koneksi MySQL/MariaDB juga dikonfigurasi. Nilai `.env` lokal tidak dibaca, sehingga koneksi runtime aktual belum diverifikasi. |
| Tujuan | Website sekolah terpadu, CMS, portal internal dan PPDB online; kebutuhan rinci ada dalam PRD yang masih berstatus draft. |
| Batas audit | Pemeriksaan source, dokumentasi, route terdaftar, PHPUnit dengan SQLite in-memory, build Vite ke direktori scratch, dan Pint dalam mode pemeriksaan. Tidak ada migration/seeder manual, instalasi dependency, akses endpoint produksi, atau pemeriksaan data rahasia. |

Saat audit dimulai, Git melaporkan tiga file aplikasi yang sudah dimodifikasi: `app/Exceptions/Ppdb/DocumentUploadException.php`, `app/Services/Ppdb/DocumentReviewService.php`, dan `app/Services/Ppdb/DocumentService.php`. Perubahan tersebut telah ada sebelum dokumentasi audit ini dibuat; isinya tidak ditimpa.

## 2. Struktur Direktori Aktual

Direktori dependency dan runtime (`vendor/`, `node_modules/`, `storage/`, `.git/`) tidak ditampilkan penuh.

```text
laravel/
├── app/
│   ├── Enums/                 # 9 enum status/visibilitas
│   ├── Exceptions/            # pengecualian domain CMS dan PPDB
│   ├── Http/
│   │   ├── Controllers/       # Auth, Admin, Public, dashboard
│   │   ├── Middleware/        # permission, role, akses sesi pendaftar
│   │   └── Requests/          # validasi form CMS/PPDB
│   ├── Models/                # 20 model Eloquent
│   ├── Policies/              # 8 policy
│   ├── Providers/
│   └── Services/
│       ├── Cms/               # workflow dan pembacaan konten publik
│       ├── Payment/           # interface, provider manual, payload event
│       ├── Ppdb/              # registrasi, dokumen, pembayaran, seleksi
│       └── AuditLogger.php
├── bootstrap/                 # bootstrap Laravel 13 dan registrasi provider
├── config/                    # termasuk auth, database, filesystem, DMR
├── database/
│   ├── factories/
│   ├── migrations/            # 27 migration
│   └── seeders/
├── docs/                      # ERD, flowchart, audit, status, rencana
├── public/                    # entry point dan aset build Vite
├── resources/
│   ├── css/app.css            # Tailwind v4 dan design tokens
│   ├── js/app.js
│   └── views/                 # Blade publik, CMS, PPDB, auth, dashboard
├── routes/
│   ├── console.php
│   └── web.php                # 66 route terdaftar saat diperiksa
├── tests/
│   ├── Feature/               # auth, CMS, fondasi, PPDB, halaman publik
│   └── Unit/
├── AGENTS.md
├── CLAUDE.md
├── composer.json / composer.lock
├── package.json / package-lock.json
├── phpunit.xml
├── PRD_Website_DMR_OpenCode.md
├── README.md
├── vite.config.js
└── PROJECT_STRUCTURE.md
```

`routes/api.php`, konfigurasi Tailwind terpisah, dan `tsconfig.json` tidak ditemukan dalam inventaris root. Tailwind v4 dipanggil melalui plugin Vite dan `@import 'tailwindcss'` di CSS.

## 3. File dan Direktori Penting

| Path | Jenis | Fungsi | Status verifikasi |
|---|---|---|---|
| `composer.json`, `composer.lock` | Composer | Dependensi, autoload, skrip Composer; Laravel framework terkunci pada 13.32.0 | Dibaca; CLI Composer tidak dapat dijalankan di shell audit |
| `package.json`, `package-lock.json` | npm | Skrip Vite dan versi frontend yang dikunci | Dibaca; build berhasil |
| `bootstrap/app.php` | Bootstrap | Route web/console, endpoint `/up`, alias middleware | Dibaca; route terdaftar |
| `routes/web.php` | Routing | Halaman publik, login, dashboard, CMS dan PPDB | Dibaca; `route:list` memuat 66 route |
| `app/Http/Controllers/` | Controller | Menangani request dan response HTTP | Ditemukan; jalur inti diuji lewat feature tests |
| `app/Http/Requests/` | Form Request | Validasi CMS, pendaftaran, status dan upload dokumen | Ditemukan |
| `app/Http/Middleware/` | Middleware | Pemeriksaan role, permission, sesi PPDB | Ditemukan; pengujian auth/PPDB lulus |
| `app/Policies/` | Authorization | Otorisasi aksi model CMS dan PPDB | Ditemukan; sebagian coverage diuji |
| `app/Services/` | Service | Transisi workflow bisnis dan audit | Ditemukan; alur CMS/PPDB yang tercakup test lulus |
| `app/Models/` | Eloquent | Entitas pengguna, CMS, media, PPDB, role dan audit | 20 file model ditemukan |
| `database/migrations/` | Skema | Tabel fondasi, role, CMS, PPDB, notifikasi dan audit | 27 file ditemukan; tidak dijalankan terhadap DB lokal dalam audit |
| `database/seeders/` | Data awal | Role/permission, sekolah, admin dan periode placeholder | 5 file ditemukan; tidak dijalankan terhadap DB lokal |
| `resources/views/` | Blade | Layout dan halaman publik, CMS, PPDB, auth, dashboard | 37 file ditemukan; halaman publik dites melalui PHPUnit |
| `resources/css/app.css` | CSS | Tailwind v4, tokens warna/spacing dan komponen dasar | Dibaca; build Vite berhasil |
| `resources/js/app.js` | JavaScript | Entry point saat ini hampir kosong | Dibaca |
| `config/dmr.php` | Konfigurasi DMR | Placeholder sekolah, fingerprint key, limit, disk dan batas upload | Dibaca tanpa membuka `.env` |
| `config/filesystems.php` | Filesystem | Disk publik dan disk privat `ppdb` di luar `public/` | Dibaca; penggunaan upload privat tercakup test |
| `phpunit.xml` | Pengujian | SQLite `:memory:`, cache/session array dan queue sync | Dibaca; sesuai konfigurasi test yang dijalankan |
| `docs/` | Dokumentasi | PRD, ERD, flowchart, status, audit integrasi dan rencana | Dokumen tersedia; beberapa status/klaim sudah tertinggal dari kode |
| `.env.example` | Template environment | Default SQLite dan variabel placeholder | Dibaca; tidak ada nilai dari `.env` yang dicatat |
| `.gitignore` | Git | Mengecualikan file lokal/rahasia | File tersedia; `.env` tidak dibaca |

## 4. Arsitektur yang Terlihat

Alur HTTP umum: `routes/web.php` → middleware Laravel dan alias `role`, `permission`, atau `registration.access` → controller → Form Request/policy → service domain → model Eloquent → koneksi database dari konfigurasi → Blade view.

- **Publik:** controller halaman memanggil `PublishedContent`. Query membatasi hasil ke revisi yang dipublikasikan dan ditautkan sebagai revisi publik; draft/revisi yang menunggu review tidak ditampilkan. Halaman tunggal yang belum memiliki isi tetap menampilkan status/konten yang disiapkan, bukan fakta sekolah rekaan.
- **CMS:** `ContentWorkflowService` mengatur draft, submit, review/permintaan revisi, publikasi, arsip, revisi baru, dan audit. Perubahan konten terbit membuat revisi terpisah agar versi publik tetap tersedia sampai revisi berikutnya diterbitkan.
- **Autentikasi:** guard `web` memakai session dan model `User`. Role/permission dibaca melalui relasi/model, lalu dibatasi middleware dan policy. Route dashboard memiliki enam jalur role, tetapi semuanya menuju controller dan view dashboard umum yang sama.
- **PPDB:** calon siswa tidak memakai akun internal. Form registrasi menghasilkan nomor dan kode akses; hash akses disimpan, sedangkan sesi terverifikasi melindungi pemeriksaan status serta akses dokumen. `EnsureRegistrationAccess` mengambil ID registrasi dari sesi.
- **Dokumen PPDB:** service menyimpan file di disk privat `ppdb`; unduhan dilakukan melalui endpoint aplikasi. Review dan unggah ulang membuat/menyimpan versi dokumen.
- **Pembayaran:** provider manual tersedia di `PaymentService`. Kontrak/provider payload dan pemroses event tersedia, tetapi daftar provider hanya memuat `manual`; tidak ada route callback/webhook provider yang terdaftar.
- **Database:** model dan migration menggambarkan alur, tetapi audit ini tidak memeriksa isi `.env` atau menguji koneksi DB runtime. Semua feature tests memakai SQLite in-memory.

## 5. Struktur Database

Terdapat 27 migration: 3 migration fondasi Laravel dan 24 migration proyek, termasuk 2 migration penguatan integritas PPDB. Tabel/kelompok implementasi yang tercermin pada migration dan model mencakup:

- Fondasi Laravel: `users`, cache, jobs serta tabel pendukung yang disediakan migration inti.
- Otorisasi: `roles`, `permissions`, `role_user`, `permission_role`.
- Sekolah: `school_units`, `programs`.
- CMS/media: `content_items`, `content_revisions`, `content_revision_reviews`, `media`, `content_revision_media`.
- PPDB: `ppdb_periods`, `ppdb_period_units`, `ppdb_requirements`, `registrations`, `registration_documents`, `payments`, `payment_events`, `selection_decisions`.
- Operasional internal: `notifications`, `audit_logs`.

Model Eloquent ditemukan untuk entitas di atas, termasuk relasi periode–unit, unit–program, konten–revisi/review, pendaftaran–dokumen/pembayaran/keputusan, dan payment–event. `docs/STATUS_MATRIX.md` menetapkan enum status; sembilan enum ditemukan di `app/Enums/`.

**Batas verifikasi:** migration tidak dijalankan ulang. `docs/PPDB_INTEGRATION_AUDIT.md` menyatakan beberapa perubahan sudah diuji pada MySQL pengembangan, tetapi pernyataan tersebut tidak diverifikasi ulang dalam audit ini. Pengujian yang dijalankan hanya memakai SQLite in-memory. Jadi keberadaan migration tidak membuktikan skema MySQL/MariaDB aktual sama dengan file migration.

ERD yang tersedia adalah `docs/ERD.md` (revisi, status tertulis belum disetujui) dan `docs/erd_sebelum_revisi.md` (versi lama). File bernama `ERD_DMR_REVISI.md` tidak ditemukan. Struktur aktual mencakup skema yang lebih baru seperti revisi konten, tabel penghubung periode-unit, sentinel integritas, dan riwayat keputusan; dokumen ERD lama memuat nama/status/constraint yang sudah tidak cocok.

## 6. Autentikasi dan Otorisasi

- Login internal tunggal tersedia pada `/login`, menggunakan guard session Laravel dan `User`.
- Alias middleware `role` dan `permission` terdaftar di `bootstrap/app.php`; `EnsureRole` dan `EnsurePermission` menolak user tanpa hak dengan HTTP 403.
- Policy tersedia untuk CMS, pengguna/role/permission, registrasi, dokumen, pembayaran dan hasil seleksi.
- Route admin PPDB dilindungi permission; CMS memakai policy per aksi. Akses status calon siswa memakai session PPDB terverifikasi, bukan akun internal.
- Feature tests untuk login dan pemisahan role lulus.
- **Belum ditemukan bukti memadai** untuk seluruh pembatasan data berdasarkan unit sekolah sebagaimana kebutuhan PRD. Juga belum ditemukan halaman/route CRUD untuk pengelolaan akun, role, permission, unit, atau program. Policy/model yang ada saja tidak membuktikan fitur UI administrasi tersedia.
- Reset kode akses tersedia sebagai route permission khusus. Prosedur verifikasi identitas sekolah sebelum reset masih menjadi keputusan/proses operasional terbuka.

## 7. Status Fitur DMR

Penghitungan berikut memakai **17 kelompok fitur** yang ditetapkan untuk ringkasan audit ini, bukan hitungan setiap butir PRD. `Implemented and verified` hanya diberikan pada alur yang memiliki bukti runtime/test/build dalam audit ini.

| Fitur | Bukti | Status | Gap | Langkah berikutnya |
|---|---|---|---|---|
| Fondasi Laravel dan pipeline aset | Laravel boot, route:list, PHPUnit dan build Vite berhasil | Implemented and verified | Pint gagal pada satu file yang sudah dimodifikasi sebelumnya; Composer CLI tidak tersedia | Periksa formatter pada perubahan aktif tanpa menimpa pekerjaan pengguna |
| Website publik 16 halaman | 16 route name diuji HTTP 200; test juga memeriksa konten terbit dan draft | Implemented and verified | Data sekolah resmi masih placeholder/konten dapat kosong; responsivitas dan aksesibilitas visual belum diuji | Isi hanya setelah data resmi disetujui; lakukan review responsif/a11y |
| CMS draft → review → publish, revisi, arsip | Service, policy, controller, view dan `ContentWorkflowTest`; suite lulus | Implemented and verified | Bukan seluruh alur CMS multimodal/semua tipe halaman yang diuji | Perluas uji coverage ketika fitur media ditambahkan |
| CMS upload/kelola media | Model dan relasi media ada; tidak ditemukan controller/route upload CMS | Not found | Media konten belum memiliki alur pengelolaan CMS yang dapat dibuktikan | Rancang/implementasikan setelah otorisasi dokumentasi tambahan bila diperlukan |
| Login, role dan permission dasar | Controller, middleware, policy, test login dan akses role lulus | Implemented and verified | Otorisasi lintas unit sekolah belum terverifikasi | Tambahkan tes scope unit dan negasi akses |
| Dashboard spesifik per role | Enam route role menuju `DashboardController` dan `dashboard.show` | Partially implemented | Halaman masih berbagi tampilan dan modul permission; dashboard tugas spesifik belum ada | Tentukan kebutuhan per role lalu pisahkan data/tampilan |
| UI CRUD akun, role, unit dan program | Model, policy dan seed tersedia, tetapi route/controller CRUD tidak ditemukan | Not found | Modul administrasi belum dapat dipakai melalui portal | Implementasikan sesuai keputusan akses dan validasi |
| Periode dan persyaratan PPDB | Controller, route, view dan `AdminPortalTest` | Implemented and verified | Belum diverifikasi dengan DB MySQL aktual | Lanjutkan uji integrasi pada database pengembangan yang terisolasi |
| Registrasi dan cek status PPDB | Controller/service, throttle, session access, `RegistrationTest` dan `StatusTest` | Implemented and verified | Field final, masa retensi, dan kebijakan privasi menunggu sekolah | Konfirmasi kebijakan sebelum produksi |
| Upload, review, koreksi, re-upload dan unduh privat | Service, disk privat, route/policy dan `DocumentTest` | Implemented and verified | Upload file aman di produksi dan retensi belum diuji operasional | Uji konfigurasi disk produksi serta backup/retensi |
| Pembayaran manual dan percobaan ulang | `PaymentService`, provider manual, attempt/event, `PaymentTest` | Implemented and verified | Nominal/metode/batas waktu masih belum diputuskan sekolah | Tetapkan kebijakan sebelum menerima pembayaran nyata |
| Pembayaran otomatis/webhook provider | Interface dan pemroses event tersedia; hanya provider manual terdaftar, callback tidak ada di route:list | Partially implemented | Tidak ada provider resmi atau endpoint webhook terintegrasi | Tunggu keputusan provider, lalu tambah validasi signature dan route/test idempotensi |
| Hasil seleksi | Service, permission, route, riwayat keputusan dan `SelectionTest` | Implemented and verified | Otoritas dan definisi hasil resmi belum dikonfirmasi | Konfirmasi pejabat/role berwenang |
| Pemulihan kode akses | Route permission dan `AccessCodeRecoveryTest` | Implemented and verified | Prosedur pembuktian identitas serta kanal aman belum ditetapkan | Tetapkan SOP sekolah sebelum operasional |
| Notifikasi internal | Model dan migration tersedia; tidak ditemukan UI/route atau integrasi event yang lengkap | Partially implemented | Penerima, pemicu, dan status baca belum dapat dipakai end-to-end | Implementasikan integrasi setelah kebutuhan penerima disepakati |
| Penampil audit log | Model dan `AuditLogger` tersedia; tidak ditemukan route/controller/view pembaca | Partially implemented | Log ditulis untuk beberapa tindakan, namun tidak ada viewer portal | Tambahkan akses viewer terbatas dan uji redaksi data sensitif |
| Responsivitas, audit aksesibilitas, Three.js, Smart Homepage/Global Search | CSS/design token ada; Three.js tidak ditemukan; PRD menunda Smart Homepage dan Global Search | Planned | Responsivitas/a11y belum diuji; fitur tertunda bukan ruang lingkup tahap ini | Uji tampilan; jangan menganggap fitur tertunda wajib tersedia |

**Ringkasan 17 kelompok:** 10 implemented and verified, 4 partially implemented, 2 not found, 1 planned/deferred. Hasil ini tidak menyatakan seluruh acceptance criteria produk telah terpenuhi.

## 8. Dependensi dan Konfigurasi

- Backend: Laravel `13.32.0`, PHP constraint `^8.3`, Laravel Tinker `3.0.2`, PHPUnit `12.5.35`, Pint `1.32.1` (versi dari lockfile).
- Frontend: Vite `8.3.4`, Tailwind CSS dan plugin Tailwind Vite `4.3.3`, Laravel Vite plugin `3.2.0`, concurrently `10.0.6`.
- Tidak ditemukan Three.js, framework SPA, API routes, atau package admin-panel pada package manifests.
- Vite entrypoints: `resources/css/app.css`, `resources/js/app.js`. CSS memuat design tokens placeholder dan Tailwind v4.
- Disk privat PPDB: `storage/app/ppdb`, `serve=false`, bukan bagian `public/`.
- `.env.example` memakai SQLite sebagai contoh default, tetapi `config/database.php` menyediakan MySQL/MariaDB. `.env` lokal sengaja tidak dibaca.
- Jangan menyalin nilai rahasia ke dokumentasi. `APP_KEY`, password, token, dan credential tidak dicantumkan.

## 9. Perintah Pengembangan yang Tersedia

Perintah berikut dideklarasikan di repository atau digunakan secara aman selama audit. Perintah setup tidak dijalankan.

| Perintah | Makna / status |
|---|---|
| `php artisan --version` | Berhasil; menunjukkan Laravel Framework 13.32.0 |
| `php artisan route:list --except-vendor` | Berhasil; menampilkan 66 route |
| `vendor/bin/phpunit --configuration phpunit.xml --do-not-cache-result` | Berhasil; 123 test, 438 assertion |
| `npm run build -- --outDir <direktori-scratch>` | Berhasil; output ditulis ke scratch di luar repository |
| `vendor/bin/pint --test` | Gagal: satu file melaporkan fixer `braces_position`, `no_unused_imports`, `single_line_empty_body` |
| `composer test` | Dideklarasikan di `composer.json`, tetapi tidak dijalankan; perintah `composer` gagal karena launcher mengarah ke `C:/ProgramData/ComposerSetup/bin/composer.phar` yang tidak tersedia |
| `composer setup` | Dideklarasikan tetapi **tidak dijalankan**. Skrip menjalankan `composer install`, key generation, `php artisan migrate --force`, instalasi npm, dan build. Jangan menjalankannya pada database berisi data tanpa rencana/izin yang tepat. Skrip tersebut tidak menjalankan `db:seed`. |
| `composer dev`, `php artisan serve`, `npm run dev` | Dideklarasikan/didokumentasikan; server tidak dijalankan dalam audit |

Build Vite berhasil dengan satu peringatan: font fallback teroptimasi memerlukan optional package `fontaine` atau konfigurasi `optimizedFallbacks: false`. Tidak ada dependency yang dipasang.

## 10. Hasil Audit dan Ketidaksesuaian Dokumentasi

- `PRD_Website_DMR_OpenCode.md` menyebut dirinya **Draft implementasi**. Ia adalah acuan kebutuhan, bukan bukti bahwa seluruh kebutuhan sudah disetujui/diimplementasikan. Biaya, provider, data resmi sekolah, SOP reset kode, retensi, dan kewenangan hasil masih terbuka.
- `README.md` dan `docs/IMPLEMENTATION_PLAN.md` masih memuat status awal yang menyebut CMS belum dibangun, sedangkan controller/service workflow CMS dan test end-to-end sudah ada. README dan implementation plan juga menyatakan 110 test lulus; eksekusi audit saat ini menunjukkan 123 test dan 438 assertion.
- `docs/PROJECT_AUDIT.md` adalah audit **kondisi awal** bertanggal 18 Oktober 2026; klaimnya bahwa repository hanya skeleton Laravel tanpa role/CMS/PPDB tidak menggambarkan repository sekarang. Dokumen tersebut bukan audit kondisi terkini.
- `docs/FLOWCHART.md` menyebut flowchart belum diimplementasikan; CMS dan sebagian besar PPDB kini memiliki kode dan test. Diagram tetap referensi rancangan, bukan bukti semua cabang alur selesai.
- `docs/ERD.md` menyatakan revisi belum disetujui dan melarang migration final sebelum persetujuan; migration proyek sudah tersedia. Bukti persetujuan formal tidak ditemukan dalam cakupan dokumen. Perlu konfirmasi, bukan asumsi bahwa ERD sudah disetujui.
- `docs/erd_sebelum_revisi.md` berisi rancangan lama yang berbeda dari `docs/ERD.md`, `docs/STATUS_MATRIX.md` dan migration. Jangan memakai rancangan lama sebagai sumber skema aktual.
- `docs/PPDB_INTEGRATION_AUDIT.md` mencatat klaim uji MySQL dan angka 110 test. Angka test sudah tidak sesuai hasil audit ini; klaim database MySQL tidak dijalankan ulang.
- `ERD_DMR_REVISI.md` tidak ditemukan; dokumen ERD revisi yang tersedia adalah `docs/ERD.md`.
- Working tree memiliki tiga file aplikasi yang sudah berubah sebelum audit. Status akhir perlu dipertahankan dan dibandingkan terhadap kondisi awal; audit tidak bermaksud mengakui perubahan tersebut sebagai hasil audit.

## 11. Rekomendasi Tahap Berikutnya

1. Konfirmasi status persetujuan ERD dan catat sumber keputusan yang berlaku sebelum pekerjaan skema lanjutan. Jangan membuat migration hanya berdasarkan nama file “revisi”.
2. Minta izin untuk menyelaraskan `README.md`, `PROJECT_AUDIT.md`, `FLOWCHART.md`, `IMPLEMENTATION_PLAN.md`, dan audit integrasi dengan kondisi sekarang; dokumen-dokumen itu tidak diubah dalam tugas ini.
3. Uji migrasi dan constraint pada database MySQL/MariaDB pengembangan yang disposable/terisolasi setelah backup dan persetujuan eksplisit. Audit ini hanya membuktikan test SQLite in-memory.
4. Lengkapi UI pengelolaan akun/role/unit/program, dashboard sesuai tugas masing-masing role, dan verifikasi pembatasan akses berbasis unit.
5. Lengkapi alur media CMS dan uji validasi MIME/ukuran, approval, serta publikasi media.
6. Implementasikan notifikasi end-to-end dan viewer audit log dengan permission minimum, redaksi rahasia, dan test.
7. Tunggu keputusan resmi provider, biaya, metode dan aturan pembayaran sebelum mengintegrasikan webhook/provider nyata.
8. Tetapkan kebijakan data sekolah, SOP reset akses, retensi/penghapusan data siswa, lalu lakukan review responsivitas, aksesibilitas dan keamanan produksi.

## 12. Batasan Audit

- Pemeriksaan kode bersifat statis di luar test yang disebutkan; tidak ada server aplikasi atau endpoint produksi yang diakses.
- `.env` tidak dibaca. Koneksi database runtime aktual, credential, konfigurasi lokal penuh, dan data sekolah belum diverifikasi.
- Tidak dijalankan migration atau seeder pada database pengembangan, tidak diperbarui dependency, dan tidak dibangun ke `public/build` dalam repository.
- PHPUnit memakai SQLite in-memory. Hasilnya memverifikasi skenario yang dites, bukan integrasi MySQL/MariaDB, konfigurasi produksi, performa, atau keamanan secara menyeluruh.
- Tidak dilakukan audit visual responsif, keyboard/screen reader, Three.js, penetrasi, maupun pemeriksaan kebijakan privasi operasional.
- Formatter melaporkan satu file bermasalah; file itu termasuk working-tree modification yang sudah ada sebelum audit dan tidak diperbaiki untuk menjaga perubahan pengguna.
