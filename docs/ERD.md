# ERD (Entity Relationship Diagram) — Website Sekolah DMR + PPDB Online

**Status dokumen:** Revisi untuk ditinjau — belum disetujui final  
**Proyek:** Website Profil Company Sekolah DMR + PPDB Online  
**Database target:** MySQL  
**Framework:** Laravel  
**Tujuan:** Mendokumentasikan entitas, atribut, primary key, foreign key, relasi, kardinalitas, batasan data, keamanan, serta keputusan yang masih terbuka sebelum migration dan implementasi dibuat.

---

## 1. Tujuan dan batasan

ERD ini mendukung satu website terintegrasi di bawah yayasan yang mengelola beberapa unit pendidikan, yaitu SMP, SMA, dan SMK.

Cakupan data:

- Pengguna internal, role, dan permission.
- CMS dengan riwayat revisi dan proses persetujuan.
- Periode PPDB yang dapat mencakup beberapa unit sekolah.
- Persyaratan dokumen yang dapat berbeda berdasarkan periode, unit, dan program.
- Pendaftaran, versi dokumen, percobaan pembayaran, dan riwayat keputusan seleksi.
- Media publik dan privat.
- Notifikasi serta audit log.

Dokumen ini **bukan migration atau SQL**. Belum boleh dibuat migration, kode aplikasi, atau perubahan database sebelum ERD disetujui pengguna.

Nama resmi yayasan dan unit sekolah masih perlu dikonfirmasi. Nama DMR digunakan sebagai identitas proyek sementara.

## 2. Prinsip rancangan

1. Satu database aplikasi digunakan oleh website terintegrasi, dengan pemisahan data berdasarkan unit sekolah bila diperlukan.
2. Tabel `users` bawaan Laravel digunakan kembali; tidak membuat tabel pengguna kedua yang menduplikasi fungsinya.
3. Role dan permission dipisahkan agar hak akses dapat diatur dengan jelas.
4. Konten CMS memiliki entitas induk dan banyak revisi. Revisi yang belum disetujui tidak boleh menggantikan konten publik yang sudah diterbitkan.
5. Satu periode PPDB dapat mencakup beberapa unit sekolah melalui tabel penghubung.
6. Satu pendaftaran memilih satu periode, satu unit sekolah, dan satu program yang sesuai. Untuk unit yang tidak menggunakan program/jurusan, representasi program masih harus ditentukan sebelum implementasi.
7. Setiap pendaftaran dapat memiliki beberapa percobaan pembayaran.
8. Setiap pendaftaran dapat memiliki beberapa keputusan seleksi yang tersimpan sebagai riwayat. Keputusan terbaru yang valid menjadi keputusan yang berlaku.
9. Setiap unggahan ulang dokumen disimpan sebagai versi baru agar riwayat sebelumnya tidak hilang.
10. Berkas PPDB privat tidak boleh diakses langsung oleh publik tanpa otorisasi.
11. Data sensitif tidak boleh ditulis ke audit log dalam bentuk rahasia atau mentah.
12. Aturan yang belum disetujui sekolah harus tetap ditandai sebagai keputusan terbuka.

## 3. Diagram ERD (Mermaid)

```mermaid
erDiagram
    USERS ||--o{ ROLE_USER : assigned
    ROLES ||--o{ ROLE_USER : contains
    ROLES ||--o{ PERMISSION_ROLE : grants
    PERMISSIONS ||--o{ PERMISSION_ROLE : assigned

    SCHOOL_UNITS ||--o{ PROGRAMS : offers

    USERS ||--o{ CONTENT_REVISIONS : authors
    USERS o|--o{ CONTENT_REVISIONS : submitted_by
    USERS o|--o{ CONTENT_REVISIONS : reviewed_by
    CONTENT_ITEMS ||--o{ CONTENT_REVISIONS : has_revisions
    CONTENT_ITEMS o|--o| CONTENT_REVISIONS : publishes_revision
    CONTENT_REVISIONS ||--o{ CONTENT_REVISION_REVIEWS : review_history
    USERS o|--o{ CONTENT_REVISION_REVIEWS : reviews
    CONTENT_REVISIONS ||--o{ CONTENT_REVISION_MEDIA : attaches
    MEDIA ||--o{ CONTENT_REVISION_MEDIA : attached_to
    USERS o|--o{ MEDIA : uploaded_by

    PPDB_PERIODS ||--o{ PPDB_PERIOD_UNITS : covers
    SCHOOL_UNITS ||--o{ PPDB_PERIOD_UNITS : included_in
    USERS o|--o{ PPDB_PERIODS : created_by
    PPDB_PERIODS ||--o{ PPDB_REQUIREMENTS : defines
    SCHOOL_UNITS ||--o{ PPDB_REQUIREMENTS : scopes
    PROGRAMS o|--o{ PPDB_REQUIREMENTS : optional_program_scope

    PPDB_PERIODS ||--o{ REGISTRATIONS : receives
    SCHOOL_UNITS ||--o{ REGISTRATIONS : selected_unit
    PROGRAMS o|--o{ REGISTRATIONS : selected_program
    REGISTRATIONS ||--o{ REGISTRATION_DOCUMENTS : document_versions
    PPDB_REQUIREMENTS ||--o{ REGISTRATION_DOCUMENTS : fulfilled_by
    MEDIA ||--o{ REGISTRATION_DOCUMENTS : stores_private_file
    USERS o|--o{ REGISTRATION_DOCUMENTS : reviewed_by

    REGISTRATIONS ||--o{ PAYMENTS : payment_attempts
    USERS o|--o{ PAYMENTS : verified_by
    PAYMENTS ||--o{ PAYMENT_EVENTS : receives_events
    REGISTRATIONS ||--o{ SELECTION_DECISIONS : decision_history
    USERS ||--o{ SELECTION_DECISIONS : decided_by

    USERS ||--o{ NOTIFICATIONS : receives
    USERS o|--o{ AUDIT_LOGS : performs

    USERS {
        bigint id PK
        string name
        string email UK
        string password
        timestamp email_verified_at
        string remember_token
        timestamps created_at
        timestamps updated_at
    }

    ROLES {
        bigint id PK
        string name UK
        string display_name
        string description
        timestamps created_at
        timestamps updated_at
    }

    PERMISSIONS {
        bigint id PK
        string name UK
        string description
        timestamps created_at
        timestamps updated_at
    }

    ROLE_USER {
        bigint user_id PK,FK
        bigint role_id PK,FK
        timestamps created_at
        timestamps updated_at
    }

    PERMISSION_ROLE {
        bigint role_id PK,FK
        bigint permission_id PK,FK
        timestamps created_at
        timestamps updated_at
    }

    SCHOOL_UNITS {
        bigint id PK
        string code UK
        string name
        string level
        string official_status
        string address
        string phone
        string email
        boolean is_active
        timestamps created_at
        timestamps updated_at
    }

    PROGRAMS {
        bigint id PK
        bigint school_unit_id FK
        string code
        string name
        text description
        boolean is_active
        timestamps created_at
        timestamps updated_at
    }

    CONTENT_ITEMS {
        bigint id PK
        bigint published_revision_id FK
        string content_type
        string status
        timestamps created_at
        timestamps updated_at
        soft_deletes deleted_at
    }

    CONTENT_REVISIONS {
        bigint id PK
        bigint content_item_id FK
        bigint author_id FK
        integer revision_number
        string title
        string slug
        text excerpt
        longtext body
        string workflow_status
        timestamp submitted_at
        bigint submitted_by FK
        timestamp reviewed_at
        bigint reviewed_by FK
        text review_note
        timestamp published_at
        timestamps created_at
        timestamps updated_at
    }

    CONTENT_REVISION_REVIEWS {
        bigint id PK
        bigint content_revision_id FK
        bigint reviewer_id FK
        string action
        text note
        timestamp reviewed_at
        timestamp created_at
    }

    MEDIA {
        bigint id PK
        bigint uploaded_by FK
        string disk
        string file_path UK
        string original_name
        string mime_type
        bigint file_size
        string storage_visibility
        string checksum
        timestamps created_at
        timestamps updated_at
    }

    CONTENT_REVISION_MEDIA {
        bigint content_revision_id PK,FK
        bigint media_id PK,FK
        string usage_type
        integer sort_order
        timestamp created_at
    }

    PPDB_PERIODS {
        bigint id PK
        string code UK
        string name
        date registration_start
        date registration_end
        date announcement_date
        string status
        decimal registration_fee
        string currency
        json payment_policy
        bigint created_by FK
        timestamps created_at
        timestamps updated_at
    }

    PPDB_PERIOD_UNITS {
        bigint ppdb_period_id PK,FK
        bigint school_unit_id PK,FK
        timestamps created_at
    }

    PPDB_REQUIREMENTS {
        bigint id PK
        bigint ppdb_period_id FK
        bigint school_unit_id FK
        bigint program_id FK nullable
        string requirement_code
        string name
        text description
        string accepted_mime_types
        bigint max_file_size
        boolean is_required
        integer sort_order
        boolean is_active
        timestamps created_at
        timestamps updated_at
    }

    REGISTRATIONS {
        bigint id PK
        bigint ppdb_period_id FK
        bigint school_unit_id FK
        bigint program_id FK nullable
        string registration_number UK
        string candidate_name
        date date_of_birth
        string parent_name
        string parent_phone
        string parent_email
        string access_code_hash
        timestamp access_code_expires_at nullable
        timestamp access_code_revoked_at nullable
        string identity_fingerprint nullable
        timestamp data_consent_at nullable
        string privacy_notice_version nullable
        string status
        timestamp submitted_at nullable
        timestamps created_at
        timestamps updated_at
    }

    REGISTRATION_DOCUMENTS {
        bigint id PK
        bigint registration_id FK
        bigint ppdb_requirement_id FK
        bigint media_id FK
        integer version_number
        string status
        bigint reviewed_by FK nullable
        timestamp reviewed_at nullable
        text review_note nullable
        timestamp uploaded_at
        timestamps created_at
        timestamps updated_at
    }

    PAYMENTS {
        bigint id PK
        bigint registration_id FK
        string provider
        string provider_reference nullable
        string payment_method nullable
        decimal amount
        string currency
        string status
        timestamp expires_at nullable
        timestamp confirmed_at nullable
        bigint verified_by FK nullable
        string verification_method
        text verification_note nullable
        timestamps created_at
        timestamps updated_at
    }

    PAYMENT_EVENTS {
        bigint id PK
        bigint payment_id FK
        string provider
        string provider_event_id
        string event_type
        string processing_status
        timestamp received_at
        timestamp processed_at nullable
        string payload_reference nullable
        timestamps created_at
    }

    SELECTION_DECISIONS {
        bigint id PK
        bigint registration_id FK
        bigint decided_by FK
        string decision_status
        text decision_note nullable
        timestamp decided_at
        boolean is_current
        timestamps created_at
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        string type
        string title
        text body
        json data nullable
        timestamp read_at nullable
        timestamps created_at
        timestamps updated_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint actor_user_id FK nullable
        string action
        string entity_type
        string entity_id
        string outcome
        string request_id nullable
        string ip_address nullable
        text description nullable
        timestamp occurred_at
    }
```

### 3.1 Catatan pembacaan diagram

- `CONTENT_ITEMS` ke `CONTENT_REVISIONS` menunjukkan konten induk yang memiliki banyak revisi.
- `CONTENT_ITEMS.published_revision_id` menunjuk revisi yang sedang menjadi versi publik. Relasi ini berbeda dari relasi kepemilikan revisi.
- Relasi referensi versi publik membentuk siklus referensi dengan `CONTENT_REVISIONS.content_item_id`. Urutan pembuatan foreign key perlu direncanakan saat migration dibuat setelah ERD disetujui.
- `PROGRAMS` bersifat opsional pada `PPDB_REQUIREMENTS` dan `REGISTRATIONS` hanya jika aturan penerimaan unit tersebut memang mengizinkannya. Aturan wajib/tidaknya program perlu dipastikan per unit.
- Diagram tidak menggantikan aturan validasi lintas tabel. Contohnya, unit dan program yang dipilih dalam pendaftaran harus sesuai satu sama lain dan harus termasuk cakupan periode PPDB.

## 4. Definisi tabel dan atribut

Keterangan:

- **PK:** Primary Key.
- **FK:** Foreign Key.
- **UK:** Unique Key.
- **nullable:** nilai boleh kosong.
- `timestamps`: `created_at` dan `updated_at`, kecuali tabel yang secara eksplisit hanya memiliki waktu kejadian.

### 4.1 `users`

Menggunakan tabel pengguna bawaan Laravel dan menyesuaikan migration bawaan bila dibutuhkan.

| Atribut | Tipe konseptual | Aturan | Keterangan |
|---|---|---|---|
| id | bigint | PK | ID pengguna internal |
| name | varchar | wajib | Nama pengguna |
| email | varchar | UK, wajib sesuai kebijakan akun | Identitas login jika login menggunakan email |
| password | varchar | wajib | Hash kata sandi, bukan kata sandi mentah |
| email_verified_at | timestamp | nullable | Waktu verifikasi email |
| remember_token | varchar | nullable | Token remember-me Laravel |
| created_at, updated_at | timestamp |  | Waktu pencatatan |

Akun calon siswa PPDB tidak diwajibkan menjadi akun `users`. Calon siswa menggunakan nomor pendaftaran dan kode akses untuk memeriksa status pendaftaran.

Hubungan pengguna dengan unit sekolah, bila diperlukan untuk guru, staf, atau siswa, harus ditetapkan lebih lanjut. Jangan menganggap seluruh pengguna hanya terikat pada satu unit tanpa keputusan kebutuhan.

### 4.2 `roles`

| Atribut | Tipe konseptual | Aturan | Keterangan |
|---|---|---|---|
| id | bigint | PK | ID role |
| name | varchar | UK | Nama teknis, misalnya `admin`, `cs_admin`, `ppdb_officer`, `teacher`, `staff`, `student` |
| display_name | varchar | wajib | Nama role untuk tampilan |
| description | text | nullable | Penjelasan role |
| timestamps | timestamp |  | Waktu pencatatan |

Role `ppdb_officer` adalah rekomendasi yang disetujui sebagai dasar rancangan. Hak finalnya tetap dibatasi oleh permission.

### 4.3 `permissions`

| Atribut | Tipe konseptual | Aturan | Keterangan |
|---|---|---|---|
| id | bigint | PK | ID permission |
| name | varchar | UK | Nama permission teknis |
| description | text | nullable | Penjelasan permission |
| timestamps | timestamp |  | Waktu pencatatan |

Contoh permission yang perlu dipertimbangkan: pengelolaan CMS, peninjauan revisi, publikasi konten, verifikasi dokumen, verifikasi pembayaran, pengelolaan periode PPDB, serta penetapan hasil seleksi.

### 4.4 `role_user`

Pivot untuk relasi many-to-many antara pengguna dan role.

| Atribut | Aturan | Keterangan |
|---|---|---|
| user_id | PK, FK → users.id | Pengguna |
| role_id | PK, FK → roles.id | Role |
| created_at, updated_at |  | Waktu pencatatan |

Constraint: unique gabungan `(user_id, role_id)` atau primary key gabungan yang sama. Satu pengguna dapat mempunyai lebih dari satu role jika kebijakan akses mengizinkan.

### 4.5 `permission_role`

Pivot untuk relasi many-to-many antara role dan permission.

| Atribut | Aturan | Keterangan |
|---|---|---|
| role_id | PK, FK → roles.id | Role |
| permission_id | PK, FK → permissions.id | Permission |
| created_at, updated_at |  | Waktu pencatatan |

Constraint: unique gabungan `(role_id, permission_id)` atau primary key gabungan yang sama.

### 4.6 `school_units`

Mewakili unit SMP, SMA, dan SMK dalam yayasan yang sama.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID unit |
| code | UK | Kode unit |
| name | wajib | Nama resmi unit, masih perlu dikonfirmasi |
| level | wajib | Jenjang, misalnya SMP, SMA, atau SMK |
| official_status | nullable | Status atau keterangan resmi jika diperlukan |
| address | nullable | Alamat unit |
| phone | nullable | Nomor telepon |
| email | nullable | Email unit |
| is_active | boolean | Apakah unit aktif |
| timestamps |  | Waktu pencatatan |

Constraint: `code` unik. Nilai `level` dibatasi ke nilai yang disepakati. Nama resmi dan data kontak belum dianggap final.

### 4.7 `programs`

Mewakili jurusan atau program pada unit sekolah. Contoh program SMK yang diketahui dalam rancangan awal adalah Otomotif dan PPLG.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID program |
| school_unit_id | FK → school_units.id | Unit pemilik program |
| code | wajib | Kode program dalam unit |
| name | wajib | Nama program |
| description | nullable | Deskripsi |
| is_active | boolean | Status program |
| timestamps |  | Waktu pencatatan |

Constraint: unique gabungan `(school_unit_id, code)`. Kode program tidak harus unik secara global apabila kode yang sama boleh digunakan pada unit berbeda.

### 4.8 `content_items`

Entitas induk yang merepresentasikan satu konten logis, misalnya berita, pengumuman, agenda, halaman profil, atau informasi fasilitas.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID konten |
| published_revision_id | FK → content_revisions.id, nullable | Revisi yang sedang menjadi versi publik |
| content_type | wajib | Jenis konten |
| status | wajib | Status entitas konten, misalnya `draft`, `published`, `archived` |
| created_at, updated_at |  | Waktu pencatatan |
| deleted_at | nullable | Soft delete jika digunakan |

**Aturan pembuat konten:** `content_revisions.author_id` menjadi sumber utama pembuat setiap revisi. `content_items.author_id` tidak diperlukan dalam rancangan ini agar tidak ada dua sumber kebenaran untuk pembuat konten. Jika sekolah kelak membutuhkan pencatatan pembuat awal konten induk, keputusan tersebut harus ditambahkan secara eksplisit.

`published_revision_id` boleh kosong untuk konten yang belum pernah dipublikasikan. Untuk konten yang sudah terbit, kolom tersebut menunjuk revisi yang telah disetujui dan diterbitkan.

### 4.9 `content_revisions`

Menyimpan setiap versi konten. Revisi baru tidak menimpa isi revisi lama.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID revisi |
| content_item_id | FK → content_items.id | Konten induk |
| author_id | FK → users.id | Pembuat revisi |
| revision_number | wajib | Nomor urut revisi untuk satu konten |
| title | wajib sesuai jenis konten | Judul |
| slug | sesuai aturan jenis konten | Identitas URL jika konten memakai slug |
| excerpt | nullable | Ringkasan |
| body | nullable sesuai jenis konten | Isi konten |
| workflow_status | wajib | Status alur revisi |
| submitted_at | nullable | Waktu diajukan |
| submitted_by | FK → users.id, nullable | Pengguna yang mengajukan |
| reviewed_at | nullable | Waktu ditinjau |
| reviewed_by | FK → users.id, nullable | Peninjau terakhir |
| review_note | nullable | Catatan tinjauan terakhir |
| published_at | nullable | Waktu revisi diterbitkan |
| timestamps |  | Waktu pencatatan |

Status revisi yang diusulkan: `draft`, `submitted`, `needs_revision`, `approved`, `published`, `rejected`, `archived`.

Constraint: unique gabungan `(content_item_id, revision_number)`.

Alur publikasi: Guru atau Staff membuat draf, mengajukan revisi, Admin meninjau, revisi dapat dikembalikan untuk diperbaiki atau disetujui. Setelah publikasi, `content_items.published_revision_id` diperbarui ke revisi baru. Versi publik sebelumnya tetap tampil sampai revisi pengganti resmi diterbitkan.

### 4.10 `content_revision_reviews`

Mencatat riwayat tindakan review per revisi.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID review |
| content_revision_id | FK → content_revisions.id | Revisi yang ditinjau |
| reviewer_id | FK → users.id | Pengguna yang melakukan review |
| action | wajib | Tindakan, misalnya `approved`, `needs_revision`, atau `rejected` |
| note | nullable | Catatan tindakan |
| reviewed_at | wajib | Waktu tindakan |
| created_at |  | Waktu pencatatan |

Setiap tindakan review membuat riwayat baru. Jangan hanya menimpa catatan review sebelumnya apabila riwayat dibutuhkan untuk audit.

### 4.11 `media`

Menyimpan metadata berkas untuk media konten publik maupun berkas privat PPDB.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID media |
| uploaded_by | FK → users.id, nullable | Pengunggah |
| disk | wajib | Nama disk penyimpanan Laravel |
| file_path | UK | Lokasi berkas internal |
| original_name | nullable | Nama asli berkas untuk metadata |
| mime_type | wajib | MIME type hasil pemeriksaan server |
| file_size | wajib | Ukuran berkas |
| storage_visibility | wajib | `public` atau `private` |
| checksum | nullable | Pemeriksaan integritas jika dibutuhkan |
| timestamps |  | Waktu pencatatan |

Media konten dihubungkan melalui `content_revision_media`. Berkas PPDB dihubungkan melalui `registration_documents`.

`storage_visibility` merupakan klasifikasi tambahan, bukan pengganti pengamanan akses. Berkas privat harus disimpan pada disk privat dan disajikan melalui endpoint terotorisasi. Validasi MIME type dan ukuran harus dilakukan di server. Nama asli berkas tidak boleh menjadi satu-satunya nama penyimpanan fisik.

### 4.12 `content_revision_media`

Pivot penghubung media dengan revisi konten.

| Atribut | Aturan | Keterangan |
|---|---|---|
| content_revision_id | PK, FK → content_revisions.id | Revisi konten |
| media_id | PK, FK → media.id | Berkas media |
| usage_type | nullable | Peran media, misalnya gambar utama atau lampiran |
| sort_order | integer | Urutan media |
| created_at |  | Waktu pengaitan |

Constraint: unique gabungan `(content_revision_id, media_id)`.

Relasi ini menggantikan kebutuhan relasi polymorphic yang tidak mempunyai foreign key eksplisit untuk media konten.

### 4.13 `ppdb_periods`

Mewakili periode penerimaan siswa. Satu periode dapat mencakup beberapa unit sekolah.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID periode |
| code | UK | Kode periode |
| name | wajib | Nama periode |
| registration_start | wajib | Awal pendaftaran |
| registration_end | wajib | Akhir pendaftaran |
| announcement_date | nullable | Tanggal pengumuman |
| status | wajib | Status periode |
| registration_fee | nilai uang | Biaya pendaftaran; nominal masih terbuka |
| currency | wajib sesuai konfigurasi | Mata uang |
| payment_policy | nullable | Konfigurasi kebijakan pembayaran yang tervalidasi |
| created_by | FK → users.id | Pembuat periode |
| timestamps |  | Waktu pencatatan |

`school_unit_id` tidak diletakkan langsung pada tabel ini. Unit yang dicakup disimpan pada `ppdb_period_units`.

Periode yang tumpang tindih diperbolehkan untuk unit atau cakupan penerimaan berbeda. Sistem perlu mencegah konflik periode yang sama-sama menerima pendaftaran untuk unit dan cakupan yang sama, sesuai aturan yang nantinya disetujui sekolah.

Nominal biaya, metode pembayaran, provider, serta batas waktu pembayaran belum final. Kolom konfigurasi bukan berarti aturan pembayarannya sudah disetujui.

### 4.14 `ppdb_period_units`

Pivot untuk menentukan unit sekolah yang termasuk dalam suatu periode.

| Atribut | Aturan | Keterangan |
|---|---|---|
| ppdb_period_id | PK, FK → ppdb_periods.id | Periode |
| school_unit_id | PK, FK → school_units.id | Unit sekolah |
| created_at, updated_at |  | Waktu pencatatan |

Constraint: unique gabungan `(ppdb_period_id, school_unit_id)` atau primary key gabungan yang sama.

Setiap pendaftaran harus memilih unit yang termasuk dalam periode melalui tabel penghubung ini.

### 4.15 `ppdb_requirements`

Mendefinisikan persyaratan dokumen per periode dan unit sekolah, dengan cakupan program opsional.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID persyaratan |
| ppdb_period_id | FK → ppdb_periods.id | Periode |
| school_unit_id | FK → school_units.id | Unit |
| program_id | FK → programs.id, nullable | Program tertentu; `NULL` berarti persyaratan umum untuk unit/periode |
| requirement_code | wajib | Kode persyaratan |
| name | wajib | Nama dokumen |
| description | nullable | Keterangan |
| accepted_mime_types | JSON, wajib | Jenis berkas yang diterima, disimpan sebagai array |
| max_file_size | sesuai kebijakan | Batas ukuran |
| is_required | boolean | Wajib atau opsional |
| sort_order | integer | Urutan tampilan |
| is_active | boolean | Status persyaratan |
| program_scope_id | integer, default 0 | Sentinel scope program: `0` berarti persyaratan umum, selain itu sama dengan `program_id` |
| timestamps |  | Waktu pencatatan |

Persyaratan harus sesuai dengan periode, unit, dan cakupan program yang dipilih dalam pendaftaran. Program yang ditetapkan pada persyaratan harus berasal dari unit sekolah yang sama.

**Constraint dan NULL:** karena perilaku unique index terhadap `NULL` berbeda menurut database, rancangan ini menargetkan MySQL dan mengharuskan validasi server ditambah strategi constraint yang menangani persyaratan umum secara benar. Salah satu opsi teknis adalah kolom scope program yang dinormalisasi menjadi nilai sentinel, kemudian dipakai dalam unique index gabungan bersama `ppdb_period_id`, `school_unit_id`, dan `requirement_code`. Detail implementasi kolom dan indeks ditetapkan saat migration dirancang.

**Implementasi aktual:** kolom sentinel `program_scope_id` (default `0`) ditambahkan bersama constraint `unique (ppdb_period_id, school_unit_id, program_scope_id, requirement_code)` pada `ppdb_requirements`. Nilai sentinel dijaga model `PpdbRequirement` pada hook `saving` sehingga selalu mengikuti `program_id`.

Tujuannya adalah mencegah duplikasi kode persyaratan pada cakupan periode, unit, dan program yang sama, termasuk saat `program_id` bernilai `NULL`.

### 4.16 `registrations`

Menyimpan data pendaftaran calon siswa. Calon siswa tidak harus mempunyai akun internal Laravel.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID pendaftaran |
| ppdb_period_id | FK → ppdb_periods.id | Periode |
| school_unit_id | FK → school_units.id | Unit yang dipilih |
| program_id | FK → programs.id, nullable sesuai aturan unit | Program yang dipilih |
| registration_number | UK | Nomor pendaftaran yang dapat digunakan untuk pemeriksaan status |
| candidate_name | wajib | Nama calon siswa |
| date_of_birth | sesuai kebutuhan sekolah | Tanggal lahir |
| parent_name | sesuai kebutuhan sekolah | Nama orang tua/wali |
| parent_phone | sesuai kebutuhan sekolah | Nomor kontak orang tua/wali |
| parent_email | nullable | Email kontak |
| access_code_hash | nullable, wajib setelah akses diaktifkan | Hash kode akses rahasia |
| access_code_expires_at | nullable | Waktu kedaluwarsa kode |
| access_code_revoked_at | nullable | Waktu kode dicabut |
| identity_fingerprint | nullable | Fingerprint HMAC untuk membantu deteksi duplikat |
| data_consent_at | nullable | Waktu persetujuan pemrosesan data |
| privacy_notice_version | nullable | Versi pernyataan privasi yang disetujui |
| status | wajib | Status pendaftaran |
| submitted_at | nullable | Waktu pendaftaran dikirim |
| timestamps |  | Waktu pencatatan |

Kode akses mentah tidak disimpan. Kode tidak ditulis ke audit log dan tidak boleh ditampilkan kembali setelah dibuat. Prosedur pemulihan dilakukan oleh CS melalui verifikasi terstruktur. Durasi masa berlaku masih merupakan keputusan terbuka.

**Deteksi pendaftaran ganda:** gunakan `identity_fingerprint` hanya setelah sekolah menetapkan data identitas yang sah untuk tujuan tersebut. Fingerprint harus dibuat menggunakan HMAC di server dengan kunci rahasia yang tidak disimpan di tabel ini. Jangan mengandalkan nama dan telepon saja sebagai identitas pasti. Kasus yang ambigu perlu diperiksa petugas.

Constraint unik untuk fingerprint tidak difinalkan sebelum kebijakan identitas ditentukan. Validasi server, penanganan pengiriman ulang (idempotency), dan pemeriksaan manual tetap diperlukan.

**Persetujuan pemrosesan data:** simpan waktu persetujuan dan versi pernyataan yang disetujui. Kebutuhan data dan isi pernyataan privasi harus ditetapkan sekolah sebelum fitur difinalkan.

Aturan integritas:
- Unit harus termasuk dalam `ppdb_period_units` untuk periode yang dipilih.
- Program, jika diisi, harus milik unit yang dipilih.
- Program harus sesuai dengan cakupan periode dan persyaratan yang berlaku.
- Satu calon siswa hanya boleh mempunyai satu pendaftaran pada periode yang sama setelah mekanisme identitas dan pencegahan duplikasi disepakati.
- Jangan membuat unique constraint berdasarkan gabungan nama, tanggal lahir, dan telepon tanpa evaluasi karena data tersebut dapat sama pada orang berbeda.

### 4.17 `registration_documents`

Menyimpan setiap versi unggahan dokumen pendaftaran, termasuk versi yang dikirim ulang setelah perbaikan.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID versi dokumen |
| registration_id | FK → registrations.id | Pendaftaran |
| ppdb_requirement_id | FK → ppdb_requirements.id | Persyaratan yang dipenuhi |
| media_id | FK → media.id | Metadata berkas privat |
| version_number | wajib | Nomor versi unggahan |
| status | wajib | Status pemeriksaan dokumen |
| reviewed_by | FK → users.id, nullable | Petugas pemeriksa |
| reviewed_at | nullable | Waktu pemeriksaan |
| review_note | nullable | Catatan pemeriksaan |
| uploaded_at | wajib | Waktu unggah |
| timestamps |  | Waktu pencatatan |

Status yang disarankan: `uploaded`, `under_review`, `approved`, `needs_revision`, dan `rejected`.

**Status `missing` tidak disimpan sebagai baris.** Status ini berarti belum ada unggahan untuk persyaratan tersebut pada pendaftaran terkait.

Constraint: unique gabungan `(registration_id, ppdb_requirement_id, version_number)`.

Saat calon siswa mengunggah ulang dokumen, buat baris versi baru. Jangan menghapus versi sebelumnya. Validasi server harus memastikan persyaratan dokumen memang berlaku untuk periode, unit, dan program pendaftar.

Berkas PPDB bersifat privat, termasuk ketika dokumen sudah disetujui. Pengunduhan hanya melalui mekanisme yang memeriksa hak akses. Hak akses Admin, CS Admin, dan Petugas PPDB perlu dibatasi berdasarkan tugasnya.

### 4.18 `payments`

Satu pendaftaran dapat memiliki beberapa percobaan pembayaran. Setiap percobaan disimpan sebagai baris terpisah.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID percobaan pembayaran |
| registration_id | FK → registrations.id, tidak unik | Pendaftaran terkait |
| provider | wajib | Provider atau sumber pembayaran |
| provider_reference | nullable | Referensi transaksi dari provider |
| payment_method | nullable | Metode pembayaran |
| amount | nilai uang | Nominal percobaan |
| currency | wajib | Mata uang |
| status | wajib | Status percobaan |
| expires_at | nullable | Batas waktu percobaan |
| confirmed_at | nullable | Waktu konfirmasi |
| verified_by | FK → users.id, nullable | Petugas verifikasi manual |
| verification_method | wajib | Misalnya `automatic` atau `manual` |
| verification_note | nullable | Catatan verifikasi |
| attempt_number | integer, default 1 | Nomor percobaan pembayaran untuk satu pendaftaran |
| is_active | boolean, default true | Percobaan yang sedang berlaku |
| active_registration_id | nullable | Sentinel percobaan aktif: `registration_id` bila `is_active`, selain itu `NULL` |
| failure_reason | nullable | Alasan percobaan gagal |
| timestamps |  | Waktu pencatatan |

`registration_id` tidak boleh unique karena pendaftaran dapat mempunyai percobaan pembayaran yang gagal, kedaluwarsa, atau diulang. Yang dijaga adalah **satu percobaan aktif per pendaftaran** melalui unique index pada sentinel `active_registration_id`; nilai `NULL` pada baris non-aktif tidak bentrok karena `NULL` diperlakukan berbeda pada unique index.

Status kanonik (lihat `docs/STATUS_MATRIX.md`): `pending_payment`, `pending_verification`, `confirmed`, `manual_review`, `failed`, dan `expired`, dengan transisi yang ditentukan secara eksplisit. Daftar ini mengikuti PRD bab 8.6 dan menggantikan daftar lama (`pending`, `paid`, `cancelled`, `requires_review`, `refunded`) supaya hanya ada satu kosakata.

Verifikasi dapat dilakukan otomatis jika didukung provider atau manual untuk kasus tertentu. Verifikasi manual harus memeriksa permission dan menghasilkan catatan audit.

**Pembayaran terkonfirmasi ganda:** jika lebih dari satu percobaan dinyatakan berhasil untuk pendaftaran yang sama, tandai untuk rekonsiliasi. Jangan otomatis mengembalikan dana tanpa pemeriksaan dan persetujuan. Status pendaftaran tetap dapat dianggap sudah dibayar jika terdapat transaksi valid yang berhasil, sementara kelebihan pembayaran ditangani melalui prosedur rekonsiliasi.

### 4.19 `payment_events`

Menyimpan peristiwa pembayaran dari provider dan hasil pemrosesannya.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID peristiwa |
| payment_id | FK → payments.id | Percobaan pembayaran |
| provider | wajib | Provider sumber peristiwa |
| provider_event_id | wajib | ID peristiwa dari provider |
| event_type | wajib | Jenis peristiwa |
| processing_status | wajib | Status pemrosesan |
| received_at | wajib | Waktu diterima |
| processed_at | nullable | Waktu diproses |
| payload_reference | nullable | Referensi ke payload yang disimpan secara aman |
| created_at |  | Waktu pencatatan |

Constraint idempotency: unique gabungan `(provider, provider_event_id)`, bukan hanya `provider_event_id`, karena ID peristiwa belum tentu unik secara global antar-provider.

Validasi signature webhook, otorisasi, serta idempotency harus dilakukan server. Jika payload mentah perlu disimpan untuk audit, rahasiakan penyimpanannya, batasi akses dan retensi, serta jangan menyimpan token, kredensial, atau data pembayaran sensitif yang tidak dibutuhkan.

### 4.20 `selection_decisions`

Menyimpan riwayat keputusan seleksi untuk satu pendaftaran.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID keputusan |
| registration_id | FK → registrations.id, tidak unik | Pendaftaran |
| decided_by | FK → users.id | Pengambil keputusan |
| decision_status | wajib | Hasil keputusan |
| decision_note | nullable | Catatan |
| decided_at | wajib | Waktu keputusan |
| is_current | boolean | Penanda keputusan yang berlaku saat ini |
| previous_status | nullable | Status keputusan sebelum perubahan ini (jejak status sebelum/sesudah) |
| current_registration_id | nullable | Sentinel keputusan berlaku: `registration_id` bila `is_current`, selain itu `NULL` |
| created_at |  | Waktu pencatatan |

Status hasil perlu ditetapkan bersama sekolah, misalnya `accepted`, `not_accepted`, `waitlisted`, atau `pending`. Nilai yang dipakai saat ini: `pending`, `accepted`, `not_accepted`, `waitlisted`.

`registration_id` tidak unik karena tabel ini menyimpan riwayat perubahan keputusan. Keputusan terbaru yang valid menjadi keputusan yang berlaku, sementara keputusan lama tetap tersimpan.

Jika `is_current` digunakan, aplikasi harus memastikan paling banyak satu keputusan aktif per pendaftaran dengan transaksi dan aturan integritas yang sesuai. **Implementasi aktual:** unique index pada sentinel `current_registration_id` memastikan paling banyak satu keputusan berlaku per pendaftaran; sentinel dijaga model `SelectionDecision` pada hook `saving`, dan pergantian keputusan harus dilakukan dalam satu transaksi.

Hanya panitia PPDB atau pimpinan sekolah yang memiliki permission khusus yang boleh menetapkan hasil akhir. Petugas pemeriksa dokumen tidak otomatis berhak mengubah hasil seleksi.

### 4.21 `notifications`

Menyimpan notifikasi internal untuk pengguna yang memiliki akun.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID notifikasi |
| user_id | FK → users.id | Penerima |
| type | wajib | Jenis notifikasi |
| title | wajib | Judul |
| body | wajib | Isi |
| data | nullable | Metadata tambahan yang aman |
| read_at | nullable | Waktu dibaca |
| timestamps |  | Waktu pencatatan |

Indeks disarankan pada `(user_id, read_at, created_at)` untuk pengambilan notifikasi per pengguna.

Notifikasi internal menjadi cakupan awal. Kanal tambahan seperti WhatsApp atau live chat bukan bagian yang dianggap final oleh ERD ini.

### 4.22 `audit_logs`

Menyimpan jejak aktivitas penting yang perlu ditelusuri.

| Atribut | Aturan | Keterangan |
|---|---|---|
| id | PK | ID log |
| actor_user_id | FK → users.id, nullable | Pelaku; boleh kosong untuk proses sistem |
| action | wajib | Jenis tindakan |
| entity_type | wajib | Jenis entitas yang terkena tindakan |
| entity_id | nullable | ID entitas terkait |
| outcome | wajib | Hasil tindakan |
| request_id | nullable | ID korelasi permintaan |
| ip_address | nullable | Informasi jaringan jika diperlukan dan sesuai kebijakan |
| description | nullable | Ringkasan aman |
| occurred_at | wajib | Waktu kejadian |

Audit log tidak boleh menyimpan kata sandi, hash atau nilai kode akses, token autentikasi, secret provider, payload sensitif mentah, atau identitas calon siswa yang tidak diperlukan.

Akses ke audit log harus dibatasi. Retensi audit log ditentukan melalui kebijakan tersendiri, bukan diasumsikan sama dengan retensi data PPDB.

## 5. Aturan unique, foreign key, dan indeks

Daftar ini adalah persyaratan rancangan; bukan SQL.

### 5.1 Unique constraint yang diperlukan

- `users.email`, sesuai kebijakan login.
- `roles.name`.
- `permissions.name`.
- `school_units.code`.
- `(school_unit_id, programs.code)`.
- `(user_id, role_id)` pada `role_user`.
- `(role_id, permission_id)` pada `permission_role`.
- `(content_item_id, revision_number)` pada `content_revisions`.
- `media.file_path`.
- `(content_revision_id, media_id)` pada `content_revision_media`.
- `ppdb_periods.code`.
- `(ppdb_period_id, school_unit_id)` pada `ppdb_period_units`.
- Scope persyaratan pada `ppdb_requirements`, termasuk kasus `program_id IS NULL`.
- `registrations.registration_number`.
- `(registration_id, ppdb_requirement_id, version_number)` pada `registration_documents`.
- `(provider, provider_event_id)` pada `payment_events`.

`payments.registration_id` dan `selection_decisions.registration_id` **tidak unique**.

### 5.2 Indeks tambahan yang disarankan

- `programs(school_unit_id, is_active)`.
- `content_items(content_type, status)`.
- `content_revisions(content_item_id, workflow_status)`.
- `ppdb_periods(status, registration_start, registration_end)`.
- `ppdb_requirements(ppdb_period_id, school_unit_id, program_id)`.
- `registrations(ppdb_period_id, school_unit_id, status)`.
- `registration_documents(registration_id, ppdb_requirement_id, version_number)`.
- `payments(registration_id, status)`.
- `payment_events(payment_id, processing_status)`.
- `selection_decisions(registration_id, decided_at)`.
- `notifications(user_id, read_at, created_at)`.
- `audit_logs(entity_type, entity_id, occurred_at)`.

Indeks akhir perlu ditinjau berdasarkan pola query dan kemampuan MySQL yang digunakan.

Status implementasi: seluruh indeks di atas sudah dibuat pada migration `2026_10_19_000023_harden_ppdb_data_integrity`, kecuali `content_items(content_type, status)` dan `content_revisions(content_item_id, workflow_status)` yang mengikuti fase CMS, serta `registration_documents(registration_id, ppdb_requirement_id, version_number)` yang sudah tercakup unique index yang ada.

### 5.3 Aturan foreign key dan penghapusan

- Role atau permission yang sudah digunakan tidak boleh dihapus sembarangan.
- Konten dan revisi yang sudah diterbitkan sebaiknya diarsipkan atau menggunakan soft delete sesuai kebijakan, bukan dihapus sehingga riwayat publikasi hilang.
- Periode, pendaftaran, pembayaran, keputusan seleksi, dan audit log yang sudah mempunyai transaksi sebaiknya tidak dihapus secara cascade tanpa kajian.
- Penghapusan media harus mempertimbangkan semua referensi dan kebijakan retensi.
- Aturan `RESTRICT`, `SET NULL`, atau `CASCADE` ditentukan per relasi ketika migration dirancang. Jangan memakai cascade global tanpa peninjauan.

**Implementasi aktual (migration `2026_10_19_000024_restrict_ppdb_foreign_keys`):** seluruh relasi data transaksional PPDB memakai `ON DELETE RESTRICT`, yaitu `ppdb_periods.created_by`, `ppdb_requirements.ppdb_period_id`, `ppdb_requirements.school_unit_id`, `ppdb_requirements.program_id`, `registrations.ppdb_period_id`, `registrations.school_unit_id`, `registrations.program_id`, `registration_documents.registration_id`, `registration_documents.ppdb_requirement_id`, `registration_documents.media_id`, `payments.registration_id`, `payment_events.payment_id`, `selection_decisions.registration_id`, dan `selection_decisions.decided_by`. Relasi yang tidak mengubah makna data tetap `SET NULL` (`media.uploaded_by`, `registration_documents.reviewed_by`, `payments.verified_by`). Pivot `ppdb_period_units` tetap `CASCADE` karena tidak menyimpan transaksi. Catatan: aturan `ON DELETE` pada SQLite ditetapkan saat tabel dibuat sehingga tidak dapat diubah tanpa build ulang tabel; migration ini hanya berlaku pada MySQL/MariaDB.

## 6. Aturan integritas lintas tabel

Aturan berikut tidak selalu dapat dijamin dengan foreign key sederhana dan harus ditegakkan di server:

1. Program yang dipilih harus milik unit sekolah yang dipilih.
2. Unit pendaftaran harus termasuk dalam periode melalui `ppdb_period_units`.
3. Program pada persyaratan dokumen harus sesuai dengan unit dan cakupan periode.
4. Dokumen yang diunggah harus mengacu pada persyaratan yang berlaku untuk pendaftar.
5. Revisi yang ditunjuk `published_revision_id` harus milik konten induk yang sama dan sudah memenuhi syarat publikasi.
6. Status pembayaran tidak otomatis mengubah status dokumen atau keputusan seleksi.
7. Webhook provider diproses secara idempoten.
8. Pembayaran ganda memerlukan rekonsiliasi, bukan perubahan status tanpa pemeriksaan.
9. Satu pendaftaran hanya memiliki satu keputusan yang berlaku pada suatu waktu.
10. Kode akses yang dicabut atau kedaluwarsa tidak boleh digunakan untuk memeriksa status.
11. Unggahan ulang dokumen menghasilkan versi baru.
12. Akses berkas privat harus memeriksa autentikasi, role, permission, dan hubungan pengguna dengan data yang diminta.
13. Semua tindakan administratif penting harus dicatat dalam audit log tanpa menyimpan rahasia.
14. Validasi duplikasi pendaftaran tidak boleh semata-mata menolak dua orang yang kebetulan memiliki nama atau kontak serupa.

## 7. Keputusan yang masih terbuka

Bagian ini wajib dipertahankan sampai sekolah memberikan keputusan.

| No. | Topik | Status dan tindak lanjut |
|---|---|---|
| 1 | Nama resmi yayasan dan unit SMP/SMA/SMK | Belum final; konfirmasi nama resmi |
| 2 | Nominal biaya pendaftaran | Belum final; ditetapkan sekolah |
| 3 | Metode pembayaran dan provider | Belum final; tentukan provider, metode, dan dukungan verifikasi otomatis |
| 4 | Batas waktu pembayaran dan aturan kedaluwarsa transaksi | Belum final; tetapkan aturan percobaan ulang |
| 5 | Jenis dokumen wajib per jenjang dan program | Belum final; sekolah meninjau daftar persyaratan |
| 6 | Identitas untuk fingerprint duplikasi | Belum final; sekolah menentukan data yang sah dan diperlukan |
| 7 | Masa berlaku kode akses | Belum final; kebijakan durasi dan pemulihan harus ditetapkan |
| 8 | Retensi data pendaftaran, dokumen, dan audit log | Belum final; tentukan periode dan prosedur penghapusan/anonimisasi |
| 9 | Kebijakan pembayaran terkonfirmasi ganda | Prosedur dasar rekonsiliasi disetujui sebagai rekomendasi; otorisasi dan alur operasional final perlu ditetapkan |
| 10 | Pernyataan privasi dan dasar pemrosesan data | Belum final; versi pernyataan harus ditentukan sekolah |
| 11 | Apakah unit tertentu wajib memilih program | Belum final; tentukan per jenjang/unit |
| 12 | Bentuk relasi guru, staf, dan siswa dengan unit sekolah | Belum final; tentukan apakah pengguna dapat terikat ke satu atau beberapa unit |
| 13 | Status akhir seleksi dan kewenangan persetujuan | Kewenangan khusus telah menjadi prinsip; status rinci dan pihak final harus dikonfirmasi |
| 14 | Tumpang tindih periode PPDB | Prinsip rancangan: boleh jika unit/cakupan berbeda; aturan pencegahan konflik operasional perlu difinalkan |
| 15 | Detail role dan permission setiap tugas | Role `ppdb_officer` dipakai sebagai dasar rancangan; matriks permission lengkap harus ditinjau |
| 16 | Kanal layanan CS | WhatsApp menjadi prioritas awal; live chat ditinjau kemudian |
| 17 | Retensi versi dokumen dan konten | Riwayat perlu dipertahankan; masa penyimpanan dan pengarsipan perlu ditetapkan |
| 18 | Kebijakan data identitas calon siswa | Tentukan field wajib, alasan pengumpulan, akses, dan perlindungan sebelum formulir difinalkan |

## 8. Keputusan yang telah dikonfirmasi untuk revisi ini

Pilihan pengguna yang menjadi dasar rancangan:

1. Constraint persyaratan dokumen: validasi server ditambah strategi constraint MySQL yang menangani `NULL`.
2. Deteksi pendaftaran ganda: gunakan pemeriksaan dan fingerprint setelah identitas ditentukan; kasus ambigu diperiksa manual.
3. Role Petugas PPDB: role terpisah `ppdb_officer` dengan permission terbatas.
4. Periode PPDB: boleh tumpang tindih bila unit atau cakupan penerimaannya berbeda.
5. Kode akses: masa berlaku dapat dikonfigurasi, kode dapat dicabut, dan nilai kode disimpan sebagai hash. Durasi final tetap terbuka.
6. Retensi data: belum diputuskan.
7. Pembayaran ganda: rekonsiliasi dan persetujuan pengembalian dana, bukan refund otomatis.
8. Persetujuan data: simpan waktu persetujuan dan versi pernyataan privasi.

Pilihan tersebut menjadi dasar dokumentasi, tetapi tidak menghapus keputusan sekolah yang masih diperlukan.

## 9. Checklist validasi sebelum ERD disetujui

- [ ] Diagram Mermaid sesuai dengan daftar entitas dan foreign key.
- [ ] Relasi `content_items.published_revision_id` tercantum dan siklus referensinya dijelaskan.
- [ ] Relasi media CMS eksplisit melalui `content_revision_media`.
- [ ] Berkas PPDB privat direlasikan melalui `registration_documents`.
- [ ] `access_code_revoked_at` sudah tercantum.
- [ ] `content_revisions.author_id` menjadi sumber pembuat revisi.
- [ ] Status `missing` dijelaskan sebagai belum adanya unggahan.
- [ ] `payments.registration_id` tidak unique.
- [ ] `selection_decisions.registration_id` tidak unique dan riwayat keputusan dipertahankan.
- [ ] Constraint persyaratan dokumen menangani `program_id = NULL`.
- [ ] Periode PPDB menggunakan `ppdb_period_units` untuk mencakup banyak unit.
- [ ] Data rahasia tidak disimpan dalam audit log.
- [ ] Daftar keputusan terbuka ditinjau bersama sekolah.
- [ ] Pengguna menyetujui ERD sebelum migration dibuat.

## 10. Status akhir dokumen

**Status: BELUM DISETUJUI FINAL.**

Dokumen ini adalah rancangan ERD revisi untuk ditinjau. Item audit yang telah dinyatakan aman dipertahankan, enam ketidakkonsistenan diperbaiki, dan keputusan yang belum ditetapkan sekolah dicatat pada bagian keputusan terbuka.

Jangan membuat migration, SQL, model, controller, route, atau kode aplikasi sebelum pengguna menyetujui ERD revisi ini.