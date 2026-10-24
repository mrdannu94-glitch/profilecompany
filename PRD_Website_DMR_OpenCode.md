# Product Requirements Document (PRD)
# Website Profil Company Sekolah DMR + PPDB Online

**Nama proyek:** Website Profil Company Sekolah DMR  
**Status dokumen:** Draft implementasi untuk OpenCode CLI  
**Bahasa:** Indonesia  
**Zona waktu aplikasi:** Asia/Jakarta  
**Target lingkungan pengembangan:** Windows, VS Code/PowerShell, Laragon, PHP, Laravel, MySQL/MariaDB

> **Catatan penting:** DMR adalah nama sementara. Nama resmi yayasan/sekolah, logo, visi-misi, foto, biaya PPDB, daftar dokumen resmi, dan kebijakan pembayaran harus diisi atau dikonfirmasi oleh pihak sekolah. Jangan mengarang informasi resmi. Gunakan placeholder yang jelas sampai data asli tersedia.

---

## 1. Ringkasan Produk

Website ini merupakan situs terpadu untuk profil dan informasi sekolah swasta di bawah satu yayasan, mencakup jenjang SMP, SMA, dan SMK. Setiap jenjang memiliki profil dan data masing-masing, tetapi dikelola melalui satu website dan satu portal internal oleh Admin pusat.

Website digunakan untuk:
- Memperkenalkan identitas, program, fasilitas, guru/staf, kegiatan, dan prestasi sekolah.
- Menyediakan berita, pengumuman, agenda, galeri, informasi kontak, dan FAQ.
- Menyediakan proses Penerimaan Peserta Didik Baru (PPDB) online, termasuk pengisian formulir, unggah dokumen, pembayaran, verifikasi, pemeriksaan status, dan pengumuman hasil.
- Menyediakan portal internal dasar untuk Admin, CS Admin, Guru, Staff, dan Siswa dengan hak akses sesuai peran.

### 1.1 Identitas awal
- Nama kerja: DMR.
- Status: sekolah swasta di bawah yayasan.
- Lokasi: Bandung, Jawa Barat.
- Jenjang: SMP, SMA, SMK.
- Program SMK yang diketahui: Otomotif dan PPLG (Pengembangan Perangkat Lunak dan Gim).
- Visi dan misi: belum tersedia; tidak boleh dibuat seolah-olah resmi.

### 1.2 Tujuan produk
1. Menyajikan informasi sekolah yang mudah ditemukan dan dipercaya.
2. Mempermudah calon siswa mengajukan pendaftaran PPDB secara online.
3. Memudahkan petugas memeriksa dokumen, pembayaran, dan status pendaftaran.
4. Memungkinkan pengelolaan konten terpusat dengan alur review dan publikasi.
5. Memberikan desain khas sekolah yang modern, menarik, aksesibel, dan tidak tampak seperti template generik.

### 1.3 Indikator keberhasilan
- Semua halaman publik utama dapat dibuka dengan baik di desktop dan perangkat seluler.
- Pengunjung dapat menemukan informasi jenjang, program, kontak, dan PPDB.
- Calon siswa dapat mengirim pendaftaran, mengunggah dokumen, dan memeriksa status menggunakan nomor pendaftaran serta kode akses rahasia.
- Data pembayaran, dokumen, dan hasil seleksi memiliki status yang terpisah dan jelas.
- Konten draft tidak dapat terlihat publik sebelum disetujui dan dipublikasikan oleh Admin.
- Hak akses diuji untuk setiap role; pengguna tidak dapat membuka fungsi di luar kewenangannya.
- Instruksi instalasi dan pengujian tersedia dalam README.

---

## 2. Instruksi Utama untuk OpenCode CLI

Bertindak sebagai software engineer senior, product-minded developer, UI/UX designer, dan QA engineer. Kerjakan proyek secara bertahap, transparan, dan dapat diuji.

### 2.1 Aturan kerja wajib
1. **Audit sebelum mengubah:** periksa direktori kerja, struktur repo, `composer.json`, `package.json`, konfigurasi Laravel, routes, migrations, model, controller, views, assets, dan test yang sudah ada.
2. Jangan menganggap repo kosong sebelum memeriksanya. Jangan menimpa atau menghapus file yang sudah ada tanpa alasan dan persetujuan yang jelas.
3. Jangan menampilkan, menyalin, atau mengubah rahasia dari `.env`. Pastikan `.env` tidak masuk Git; sediakan `.env.example` tanpa secret.
4. Jangan langsung membuat seluruh aplikasi dalam satu perubahan besar. Buat rencana, implementasikan per fase, lalu uji tiap fase.
5. Jangan mengarang data sekolah resmi, statistik, testimoni, nama guru, prestasi, biaya, jadwal, atau dokumen wajib. Gunakan placeholder yang diberi label dengan jelas.
6. Jangan membuat tabel database hanya karena terlihat berguna. Setiap tabel harus punya alasan yang terkait kebutuhan dalam PRD.
7. **Urutan desain data:** tulis flowchart alur penting terlebih dahulu, kemudian ERD Mermaid dengan entitas, atribut, PK, FK, relasi, dan kardinalitas. Tampilkan dan jelaskan rancangan ERD sebelum membuat migration/schema final. Jangan menulis SQL/migration final sebelum rancangan ERD disetujui pengguna. Jika pengguna meminta pengerjaan langsung tanpa jeda, tampilkan ERD terlebih dahulu di dokumen, tandai sebagai rancangan awal, lalu lanjutkan hanya bila instruksi pengguna mengizinkan.
8. Gunakan Laravel conventions dan komponen yang sudah tersedia. Jangan memasang ulang dependency yang sudah terpasang tanpa alasan.
9. Sebelum menambah paket, jelaskan manfaatnya dan periksa kompatibilitas dengan versi PHP/Laravel serta package lock yang ada.
10. Jangan menyatakan tes berhasil jika tes belum benar-benar dijalankan. Laporkan perintah, hasil, dan tes yang belum bisa dijalankan.
11. Jangan membangun modul nilai, absensi, e-learning, pembayaran SPP, atau akademik lanjutan tanpa persetujuan baru.
12. Jika keputusan yang belum tersedia bisa dijadikan konfigurasi Admin atau placeholder, lanjutkan dengan pendekatan tersebut. Tanyakan hanya jika detail yang hilang menghalangi arsitektur, keamanan, atau alur inti.

### 2.2 Tahapan kerja yang disarankan
- **Fase 0 — Audit:** cek repo, lingkungan, dependensi, dan kondisi awal.
- **Fase 1 — Perencanaan:** dokumentasikan asumsi, keputusan, flowchart, ERD awal, rencana rute, role/permission, dan desain visual.
- **Fase 2 — Fondasi:** konfigurasi aplikasi, autentikasi, role/permission, layout, design tokens, dan data awal yang jelas ditandai sebagai placeholder.
- **Fase 3 — Website publik:** implementasikan halaman publik dan CMS.
- **Fase 4 — PPDB:** formulir, akses status, dokumen, pembayaran, verifikasi, dan hasil.
- **Fase 5 — Portal internal:** dashboard sesuai role, notifikasi internal, dan audit log.
- **Fase 6 — QA dan dokumentasi:** test, pemeriksaan akses, responsive review, README, dan laporan hasil.

Di akhir setiap fase, ringkas file yang dibuat/diubah, fitur yang selesai, perintah pengujian yang dijalankan, hasilnya, dan pekerjaan tersisa.

---

## 3. Ruang Lingkup

### 3.1 Termasuk dalam versi awal
- Website publik terintegrasi untuk SMP, SMA, dan SMK di bawah satu yayasan.
- CMS konten dengan draft, review Admin, dan publikasi.
- Portal login internal dengan role berbeda.
- PPDB online dengan nomor pendaftaran dan kode akses rahasia.
- Unggah dokumen dan koreksi dokumen.
- Biaya pendaftaran dan verifikasi pembayaran hybrid.
- Pemeriksaan status dan pengumuman hasil PPDB.
- Notifikasi di dalam portal internal.
- Pengelolaan periode PPDB, jenjang, program, persyaratan, konten, akun, dan hak akses.
- Desain responsif, aksesibilitas dasar, keamanan, dan dokumentasi.

### 3.2 Tidak termasuk kecuali disetujui kemudian
- Sistem nilai, rapor, absensi, jadwal pelajaran, e-learning, ujian online.
- Pembayaran SPP atau keuangan sekolah secara menyeluruh.
- Aplikasi mobile native.
- Smart Homepage dan Global Search tingkat lanjut; keduanya ditunda ke fase pengembangan berikutnya.
- Live chat langsung; untuk awal, kontak CS menggunakan WhatsApp.
- Integrasi otomatis dengan sistem pemerintah atau industri yang belum ditentukan.

---

## 4. Jenis Pengguna dan Hak Akses

Gunakan prinsip least privilege: setiap role hanya mendapat akses yang diperlukan. Implementasikan otorisasi di server, bukan hanya menyembunyikan tombol di UI.

### 4.1 Pengunjung publik
- Melihat informasi sekolah, jenjang, program, fasilitas, guru/staf yang dipublikasikan, berita, pengumuman, agenda, galeri, FAQ, kontak, dan informasi PPDB.
- Menghubungi CS melalui tautan WhatsApp resmi yang dikonfigurasi.
- Tidak membutuhkan akun.

### 4.2 Calon siswa PPDB
- Mengisi satu formulir pendaftaran untuk satu jenjang dan satu program dalam satu periode.
- Mengunggah dokumen yang disyaratkan.
- Mendapat nomor pendaftaran dan kode akses rahasia.
- Memeriksa status, catatan koreksi, pembayaran, dan hasil menggunakan nomor pendaftaran serta kode akses.
- Mengunggah ulang dokumen yang dikembalikan untuk diperbaiki.
- Tidak boleh melihat data pendaftar lain.

### 4.3 Orang tua/wali
- Tidak memiliki akun pada versi awal.
- Dapat membaca informasi publik dan menghubungi CS.

### 4.4 Admin pusat
- Mengelola data seluruh jenjang SMP/SMA/SMK.
- Mengelola akun, role, hak akses, program, periode PPDB, persyaratan dokumen, nominal biaya, CMS, publikasi, dan konfigurasi umum.
- Meninjau dan menerbitkan konten yang diajukan Guru/Staff.
- Mengelola pengaturan integrasi pembayaran jika tersedia.
- Melihat log audit dan laporan yang relevan.
- Mengelola keputusan/hasil PPDB hanya sesuai kewenangan yang ditetapkan.

### 4.5 CS Admin
- Menangani pertanyaan calon siswa.
- Mengarahkan pengguna ke WhatsApp dan prosedur bantuan.
- Membantu pemulihan kode akses melalui verifikasi identitas terstruktur.
- Tidak boleh melihat atau mengubah data sensitif di luar kebutuhan tugasnya.
- Tidak boleh mengungkap kode akses lama; lakukan reset/penerbitan kode baru dengan prosedur aman.

### 4.6 Petugas PPDB
- Memeriksa dokumen pendaftar.
- Menyetujui dokumen atau mengembalikannya dengan catatan koreksi.
- Memeriksa pembayaran manual untuk kasus yang memerlukan pemeriksaan.
- Tidak otomatis memiliki hak menentukan hasil akhir seleksi kecuali diberi izin khusus.

> Petugas PPDB dapat berupa permission khusus yang diberikan kepada Admin/CS Admin/staf terpilih, atau role tersendiri bila audit kebutuhan menunjukkan perlu. Jangan menyamakan semua CS Admin dengan petugas PPDB secara otomatis.

### 4.7 Guru dan Staff
- Dapat login melalui satu halaman login internal.
- Dapat membuat/mengedit draft konten sesuai izin.
- Draft dikirim kepada Admin untuk diperiksa; Guru/Staff tidak memublikasikan langsung pada versi awal.
- Akses data internal dibatasi menurut role dan jenjang yang ditetapkan.

### 4.8 Siswa
- Dapat login melalui halaman login internal.
- Mendapat Dashboard Siswa.
- Pada versi awal, hanya mengakses fitur siswa yang secara eksplisit disetujui; jangan menambahkan modul akademik tanpa keputusan baru.

### 4.9 Alumni, industri, dan pemerintah
- Tidak memerlukan login untuk informasi publik pada versi awal.
- Fitur portal khusus untuk kelompok ini ditunda sampai kebutuhannya ditetapkan.

### 4.10 Satu pintu login internal
Sediakan satu halaman login untuk Admin, CS Admin, Guru, Staff, dan Siswa. Setelah autentikasi, arahkan pengguna ke dashboard sesuai role dan permission. Jangan mengandalkan nama role dari input pengguna; role harus berasal dari data server.

---

## 5. Halaman Website Publik

Semua halaman harus responsif, memiliki navigasi yang konsisten, judul yang jelas, metadata dasar, status loading/empty/error yang masuk akal, serta konten yang hanya menampilkan data berstatus published.

1. **Homepage** — identitas sekolah, pengantar, pilihan jenjang, program unggulan yang telah dikonfirmasi, sorotan berita/kegiatan, ajakan melihat PPDB, dan kontak.
2. **Profil Sekolah** — profil yayasan dan sekolah; tampilkan profil per jenjang dengan pemisahan yang jelas.
3. **Visi & Misi** — konten yang dapat dikelola Admin. Jika belum ada data resmi, tampilkan placeholder internal atau status “informasi sedang disiapkan”; jangan menulis visi/misi fiktif.
4. **Sambutan Kepala Sekolah** — teks, nama, jabatan, dan foto hanya jika disediakan/diotorisasi.
5. **Guru & Staff** — profil publik yang telah disetujui untuk dipublikasikan; lindungi informasi pribadi yang tidak relevan.
6. **Jurusan/Program** — program per jenjang; SMK mencakup Otomotif dan PPLG berdasarkan data awal, tetapi deskripsi rinci harus dikonfirmasi.
7. **Fasilitas** — daftar dan foto fasilitas yang benar-benar tersedia.
8. **Ekstrakurikuler** — kegiatan yang dikonfirmasi sekolah.
9. **Prestasi** — prestasi dengan tanggal/kategori dan bukti/keterangan jika tersedia; jangan mengarang statistik.
10. **Berita** — daftar, detail, kategori, tanggal publikasi, gambar, dan slug unik.
11. **Pengumuman** — daftar pengumuman, tanggal, status publikasi, dan detail.
12. **Agenda/Kegiatan** — daftar kegiatan, tanggal/waktu, lokasi jika diketahui, dan status kegiatan.
13. **Galeri & Video** — foto/video yang disetujui; optimalkan ukuran media dan aksesibilitas.
14. **Informasi PPDB** — periode aktif, langkah pendaftaran, persyaratan, biaya yang telah ditetapkan, metode pembayaran yang tersedia, jadwal resmi, FAQ, dan tombol daftar/status.
15. **Kontak** — alamat yang dikonfirmasi, nomor/WhatsApp resmi, email, jam layanan, dan peta jika alamat resmi tersedia.
16. **FAQ** — pertanyaan umum yang dapat dikelola Admin.

### Navigasi jenjang
Pengunjung harus dapat memilih SMP, SMA, atau SMK. Konten spesifik jenjang harus difilter sesuai pilihan. Jangan mencampur profil, program, guru, atau berita jenjang tanpa penanda konteks yang jelas. Admin pusat tetap dapat mengelola semua jenjang dari satu portal.

---

## 6. Arahan UI/UX: Khas, Modern, Tidak Generik

### 6.1 Arah visual
Gunakan pendekatan **editorial modern + cerita manusia + visual interaktif terukur**. Situs harus terasa seperti identitas sekolah yang nyata, bukan hasil penggantian logo pada template generik.

Hindari:
- Hero yang selalu berupa teks kiri dan gambar kanan tanpa konsep.
- Semua bagian memakai grid kartu yang sama.
- Gradien ungu-biru dan glassmorphism hanya sebagai hiasan.
- Dashboard berisi kartu statistik berwarna tanpa makna.
- Ikon generik berlebihan, animasi tanpa tujuan, dan angka statistik palsu.
- Foto stok yang disajikan seolah-olah foto asli sekolah.

### 6.2 Sistem desain
Buat design tokens terpusat untuk:
- Warna utama, warna pendukung, warna status, latar, teks, dan border.
- Tipografi judul, isi, caption, dan angka.
- Skala spacing, radius, shadow, breakpoint, lebar konten, dan grid.
- Fokus keyboard, hover, active, disabled, error, success, loading, dan empty state.
- Aturan penggunaan foto, rasio gambar, crop, ikon, serta motion.

Jangan mengunci warna final sebelum logo/identitas resmi tersedia. Untuk tahap awal, pilih palet sementara yang mudah diganti melalui design tokens.

### 6.3 Perbedaan visual antarjenjang
Jaga satu identitas yayasan, tetapi berikan aksen dan cerita visual yang membedakan SMP, SMA, dan SMK. Untuk SMK, program Otomotif dan PPLG dapat memiliki subvisual masing-masing, tanpa membuat seluruh website terasa seperti situs berbeda.

### 6.4 Three.js dan gerak
- Three.js diinginkan untuk menambah kesan “hidup” pada halaman publik, tetapi digunakan selektif pada bagian yang benar-benar mendukung cerita visual.
- Jangan menjadikan animasi 3D sebagai syarat untuk membaca atau menggunakan situs.
- Muat secara lazy/dinamis hanya saat dibutuhkan.
- Sediakan fallback gambar/ilustrasi statis jika WebGL tidak tersedia atau gagal.
- Hormati `prefers-reduced-motion` dan sediakan cara menghindari animasi berat.
- Hindari efek yang memperlambat perangkat seluler, mengganggu aksesibilitas, atau menghalangi tombol/formulir.
- Ukur performa sebelum dan sesudah penambahan Three.js.

### 6.5 Responsif dan aksesibilitas
- Prioritaskan pengalaman mobile, tablet, dan desktop.
- Navigasi dapat digunakan dengan keyboard.
- Gunakan heading berurutan, label form, pesan error yang jelas, alt text yang bermakna, kontras memadai, dan indikator fokus.
- Jangan mengandalkan warna saja untuk menunjukkan status.
- Hindari layout shift; gunakan ukuran/aspect ratio media yang sesuai.

### 6.6 Portal internal
Portal internal harus berorientasi pada tugas, ringkas, dan mudah dibaca. Prioritaskan tabel yang bisa dicari/filter sesuai kebutuhan, status yang jelas, aksi utama yang menonjol, dan konfirmasi untuk tindakan sensitif. Jangan memenuhi dashboard dengan grafik/statistik yang belum memiliki data bermakna.

---

## 7. CMS dan Alur Publikasi

### 7.1 Jenis konten
CMS versi awal dapat mengelola berita, pengumuman, agenda/kegiatan, galeri/video, profil jenjang, visi-misi, sambutan kepala sekolah, guru/staff publik, program, fasilitas, ekstrakurikuler, prestasi, FAQ, informasi kontak, dan informasi PPDB yang relevan.

### 7.2 Workflow wajib
1. Guru/Staff membuat atau mengubah draft.
2. Pengguna menyimpan draft dan mengirimkannya untuk review.
3. Admin meninjau isi, media, kategori, jenjang, dan informasi publik.
4. Admin dapat menyetujui/mempublikasikan atau mengembalikan dengan catatan revisi.
5. Hanya konten berstatus published yang terlihat pada website publik.
6. Perubahan pada konten yang sudah dipublikasikan harus melalui review Admin kembali.
7. Catat pembuat, waktu perubahan, reviewer, waktu keputusan, dan publisher pada audit trail yang relevan.

Status konten yang disarankan: `draft`, `submitted`, `needs_revision`, `approved`, `published`, `archived`. Sesuaikan implementasi agar status tidak memungkinkan publikasi tanpa izin.

### 7.3 Media
- Validasi jenis file, ukuran, dan MIME type di server.
- Gunakan nama file acak/aman, bukan nama dari pengguna sebagai path.
- Batasi upload sesuai kebutuhan dan cegah eksekusi file.
- Simpan alt text/caption bila relevan.
- Media yang belum disetujui tidak boleh tampil publik.

---

## 8. PPDB Online

### 8.1 Prinsip utama
- Satu calon siswa hanya boleh membuat satu pendaftaran untuk satu periode PPDB pada versi awal.
- Satu formulir hanya memilih satu jenjang dan satu program.
- Periode PPDB ditentukan Admin.
- Persyaratan dokumen dapat diatur Admin menurut jenjang dan periode.
- PPDB berbayar; nominal resmi belum ditentukan dan harus dikonfigurasi Admin.
- Status pendaftaran, dokumen, pembayaran, dan hasil seleksi adalah status yang terpisah.

### 8.2 Alur calon siswa
1. Membuka halaman Informasi PPDB.
2. Memilih periode aktif, jenjang, dan satu program yang tersedia.
3. Mengisi data sesuai formulir yang dikonfigurasi.
4. Menyetujui pernyataan privasi/pemrosesan data yang sesuai.
5. Mengunggah dokumen yang diminta.
6. Server memvalidasi data dan mencegah pendaftaran ganda sesuai aturan yang disetujui.
7. Sistem menerbitkan nomor pendaftaran dan kode akses rahasia setelah pendaftaran berhasil dibuat. Tampilkan kode sekali dengan peringatan agar disimpan secara aman; jangan menyimpan kode dalam bentuk teks biasa.
8. Calon siswa melihat instruksi dan status pembayaran.
9. Pembayaran diverifikasi otomatis jika penyedia mendukung integrasi yang valid, atau manual untuk kasus tertentu.
10. Petugas memeriksa dokumen; dokumen dapat disetujui atau dikembalikan dengan catatan.
11. Calon siswa mengunggah ulang dokumen yang perlu diperbaiki melalui halaman status.
12. Panitia PPDB atau pimpinan yang ditunjuk dengan izin khusus menetapkan hasil akhir.
13. Calon siswa memeriksa status dan hasil dengan nomor pendaftaran serta kode akses rahasia.

### 8.3 Pemeriksaan status
- Akses menggunakan nomor pendaftaran dan kode akses rahasia, tanpa harus membuat akun internal.
- Gunakan rate limiting, pesan error yang tidak membocorkan apakah nomor pendaftaran tertentu terdaftar, dan logging yang tidak menyimpan kode akses mentah.
- Jangan menampilkan data pribadi berlebihan pada halaman status.
- Terapkan sesi/token yang aman dan masa berlaku yang sesuai untuk akses sensitif.

### 8.4 Data formulir
Field rinci belum ditetapkan. Mulai dengan rancangan yang dapat dikonfigurasi dan usulkan daftar field untuk persetujuan sebelum finalisasi. Field potensial meliputi identitas calon siswa, kontak, alamat, informasi sekolah asal, pilihan jenjang/program, kontak wali yang diperlukan, dan persetujuan pemrosesan data. Kumpulkan hanya data yang memang diperlukan.

### 8.5 Dokumen
- Jenis dokumen wajib dapat dikonfigurasi Admin per jenjang dan periode.
- Jangan menganggap daftar dokumen umum sebagai kebijakan resmi sekolah.
- Setiap dokumen memiliki status, catatan petugas, versi/re-upload bila diperlukan, waktu unggah, dan siapa yang memeriksa.
- Status contoh: `missing`, `uploaded`, `under_review`, `approved`, `needs_revision`, `rejected`.
- Bila perlu perbaikan, petugas wajib memberi alasan/catatan yang dapat dibaca calon siswa.
- Dokumen harus disimpan privat dan diakses melalui endpoint yang memeriksa otorisasi, bukan URL publik permanen.
- Validasi ukuran, ekstensi, MIME type, dan konten file jika memungkinkan. Jangan percaya nama ekstensi saja.

### 8.6 Pembayaran PPDB
Model yang disepakati adalah **verifikasi hybrid**:
- Verifikasi otomatis jika provider mendukung integrasi yang layak dan dapat diverifikasi secara server-to-server.
- Verifikasi manual untuk transaksi tertentu, bukti yang memerlukan pemeriksaan, gangguan provider, atau kasus pengecualian.

Keputusan yang belum tersedia dan tidak boleh dikarang:
- Nominal biaya.
- Provider pembayaran.
- Metode pembayaran yang diaktifkan.
- Batas waktu pembayaran.
- Kebijakan keterlambatan, pengembalian dana, dan pembayaran ulang.

Buat arsitektur provider-adapter agar provider dapat ditambahkan kemudian. Sediakan alur manual yang aman tanpa menganggap unggahan bukti sebagai konfirmasi pembayaran.

Status pembayaran yang disarankan:
- `pending_payment` — menunggu pembayaran.
- `pending_verification` — menunggu pemeriksaan.
- `confirmed` — pembayaran terkonfirmasi.
- `manual_review` — perlu pemeriksaan manual.
- `failed` — pembayaran gagal.
- `expired` — kedaluwarsa sesuai kebijakan yang dikonfigurasi.

Persyaratan teknis:
- Validasi callback/webhook provider di server menggunakan signature atau mekanisme resmi provider.
- Jangan percaya status pembayaran dari query string/browser calon siswa.
- Gunakan idempotency agar webhook berulang tidak membuat transaksi ganda.
- Simpan referensi transaksi, jumlah, mata uang, status, timestamp, dan jejak perubahan yang diperlukan.
- Jangan menyimpan data kartu atau kredensial pembayaran sensitif sendiri.
- Pisahkan status pembayaran dari status dokumen dan hasil seleksi.
- Bila provider belum dipilih, buat interface/kontrak dan implementasi manual yang aman; jangan berpura-pura integrasi otomatis sudah aktif.

**Rekomendasi default yang masih perlu konfirmasi sekolah:** calon siswa menyelesaikan formulir terlebih dahulu, kemudian menerima nomor pendaftaran/kode akses dan instruksi pembayaran. Jadikan urutan ini dapat disesuaikan sampai kebijakan resmi disetujui.

### 8.7 Verifikasi dan keputusan hasil
- Petugas PPDB memeriksa kelengkapan dan validitas dokumen.
- Petugas dapat menyetujui atau mengembalikan dokumen dengan catatan.
- Keputusan akhir hasil seleksi hanya dapat dibuat panitia PPDB atau pimpinan yang ditunjuk dengan permission khusus.
- Catat pelaku, waktu, status sebelum/sesudah, dan alasan perubahan.
- Hasil tidak boleh diumumkan sebelum otoritas yang berwenang menetapkannya.

### 8.8 Pemulihan kode akses
- Calon siswa menghubungi CS melalui kanal resmi.
- CS mengikuti prosedur verifikasi identitas terstruktur yang ditentukan sekolah.
- Jangan pernah mengirimkan atau menampilkan kembali kode lama dalam bentuk teks biasa.
- Setelah verifikasi, terbitkan kode baru secara aman, batalkan kode lama, dan catat tindakan dalam audit log.
- Detail pertanyaan verifikasi harus ditentukan sekolah; jangan menggunakan informasi yang mudah ditebak sebagai satu-satunya faktor.

---

## 9. Dashboard dan Notifikasi Internal

### 9.1 Dashboard per role
- **Admin:** ringkasan tugas review konten, periode PPDB, aktivitas yang perlu ditangani, dan pintasan manajemen.
- **CS Admin:** antrean bantuan atau aktivitas yang memang diizinkan; jangan tampilkan semua data pendaftar tanpa kebutuhan.
- **Petugas PPDB:** daftar dokumen menunggu review, kasus koreksi, dan pembayaran manual jika diberi permission.
- **Guru:** draft konten, status review, dan catatan revisi.
- **Staff:** draft/konten dan tugas sesuai permission.
- **Siswa:** Dashboard Siswa dengan informasi dasar dan fitur yang disetujui untuk versi awal.

Dashboard tidak boleh menampilkan statistik palsu. Jika belum ada data, tampilkan empty state yang informatif.

### 9.2 Notifikasi
Notifikasi versi pertama berada di portal internal. Contoh pemicu:
- Draft dikirim untuk review.
- Konten dikembalikan untuk revisi atau dipublikasikan.
- Dokumen PPDB baru diunggah atau memerlukan koreksi.
- Pembayaran perlu pemeriksaan manual atau berubah status.
- Periode PPDB mendekati tanggal yang dikonfigurasi, jika fitur pengingat disetujui.

Setiap notifikasi memiliki penerima, waktu, status dibaca, tautan internal yang aman, dan konteks secukupnya. Jangan memasukkan kode akses atau dokumen sensitif ke isi notifikasi.

### 9.3 Fitur ditunda
Smart Homepage dan Global Search tingkat lanjut ditunda. Jangan memasukkannya sebagai deliverable versi pertama kecuali pengguna meminta perubahan scope.

---

## 10. Rekomendasi Teknologi

Audit kondisi repo terlebih dahulu; gunakan versi yang sudah terpasang jika cocok.

- **Backend:** Laravel sesuai versi proyek yang ada.
- **Bahasa:** PHP sesuai versi yang kompatibel dengan Laravel/dependensi.
- **Database:** MySQL atau MariaDB melalui Laragon.
- **Frontend:** Blade, CSS/JavaScript, dan Vite bila sesuai dengan fondasi repo.
- **CSS:** Tailwind CSS boleh digunakan jika sudah tersedia atau instalasi disetujui dan kompatibel. Jangan menambah framework frontend besar tanpa alasan.
- **3D:** Three.js untuk elemen publik yang selektif; lazy loading dan fallback wajib.
- **Testing:** PHPUnit/Pest sesuai kondisi repo; tambahkan browser test seperti Playwright bila lingkungan dan waktu mendukung.
- **Version control:** Git; pastikan `.env`, kredensial, file upload pribadi, dan secret tidak ter-commit.

Jangan mengubah stack ke framework lain tanpa alasan teknis yang jelas dan persetujuan pengguna.

---

## 11. Keamanan, Privasi, dan Audit

Wajib implementasikan:
- Validasi input di server dan validasi frontend sebagai bantuan UX saja.
- Proteksi CSRF untuk form yang relevan.
- Password di-hash menggunakan mekanisme Laravel yang direkomendasikan.
- Middleware authorization/policy untuk setiap aksi sensitif.
- Rate limiting pada login, status PPDB, pemulihan kode, dan endpoint rentan disalahgunakan.
- Escaping output dan pencegahan XSS; jangan merender HTML konten tanpa sanitasi yang tepat.
- Query aman melalui ORM/query builder; hindari SQL injection.
- Validasi upload, penyimpanan file privat, dan akses file berdasarkan authorization.
- Session/cookie aman sesuai lingkungan deployment.
- Audit log untuk perubahan role/permission, publikasi konten, review dokumen, pembayaran manual, perubahan hasil, dan reset kode akses.
- Hindari menyimpan password/kode akses/token mentah di log.
- Terapkan prinsip minimisasi data dan dokumentasikan data pribadi yang dikumpulkan serta tujuannya.
- Pisahkan konfigurasi lokal dan produksi; jangan gunakan credential contoh yang tampak nyata.
- Tampilkan pesan error kepada pengguna tanpa stack trace atau detail internal pada production.
- Sediakan backup database dan file penting sebagai rekomendasi operasional; dokumentasikan proses restore.

Data calon siswa dan dokumen PPDB bersifat sensitif. Batasi akses berdasarkan tugas dan simpan file di lokasi privat. Jangan menampilkan file PPDB melalui direktori publik yang dapat ditebak.

---

## 12. Kandidat Entitas ERD Awal

Daftar ini adalah **kandidat untuk dianalisis**, bukan instruksi untuk langsung membuat semua tabel. Validasi dan sederhanakan setelah flowchart serta ERD disusun. Setiap entitas harus punya alasan kebutuhan.

- `users` — akun internal.
- `roles` / `permissions` atau mekanisme authorization Laravel yang sesuai — role dan hak akses.
- `school_units` — jenjang/unit SMP, SMA, SMK.
- `programs` — program/jurusan terkait unit.
- `content_items` — konten CMS dengan jenis, status, unit, author, dan metadata publikasi; pertimbangkan apakah semua jenis konten bisa disatukan secara rapi atau perlu pemisahan yang justified.
- `content_reviews` atau audit workflow setara — riwayat review/revisi jika tidak cukup ditangani oleh audit log.
- `media` — metadata media dan lokasi penyimpanan yang aman.
- `ppdb_periods` — periode PPDB.
- `ppdb_requirements` — daftar dokumen yang diperlukan per periode/jenjang.
- `registrations` — data pendaftaran, nomor pendaftaran, akses kode dalam bentuk hash, periode, jenjang, program, dan status utama.
- `registration_documents` — file, jenis dokumen, versi, status, catatan koreksi, pemeriksa.
- `payments` — transaksi, jumlah, referensi provider, status, dan data verifikasi yang diperlukan.
- `payment_events` — event/webhook atau riwayat status untuk idempotency/audit jika dibutuhkan.
- `selection_decisions` — keputusan hasil akhir, otoritas, waktu, dan catatan yang sesuai.
- `notifications` — notifikasi internal.
- `audit_logs` — jejak aktivitas sensitif.

Pertimbangkan tabel tambahan hanya jika ada kebutuhan jelas. Gunakan PK/FK, unique constraint, index, nullable/non-nullable yang tepat, soft delete hanya bila diperlukan, dan aturan retensi data. Tentukan bagaimana pendaftaran ganda dicegah; jangan mengandalkan validasi frontend.

### 12.1 Diagram alur yang perlu disiapkan sebelum migration
Buat Mermaid flowchart untuk setidaknya:
1. CMS: draft → submit → review Admin → revisi atau publish.
2. PPDB: formulir → validasi → nomor pendaftaran/kode akses → pembayaran → verifikasi dokumen → koreksi bila perlu → keputusan hasil.
3. Pembayaran: pembayaran otomatis yang tervalidasi atau jalur manual/pengecualian.
4. Login internal: autentikasi → pemeriksaan role/permission → dashboard yang sesuai.

### 12.2 ERD
Sediakan ERD Mermaid `erDiagram` yang menampilkan atribut inti, PK/FK, dan kardinalitas. Jelaskan alasan tiap entitas dan constraint penting. Jangan membuat migration final sampai rancangan ERD mendapat persetujuan pengguna atau pengguna secara eksplisit memerintahkan lanjut tanpa jeda.

---

## 13. Validasi dan Acceptance Criteria

### 13.1 Website publik
- Semua 16 halaman tersedia dan navigasinya konsisten.
- Konten draft/revisi tidak muncul di halaman publik.
- Pilihan SMP/SMA/SMK memfilter konten dengan benar.
- Tampilan berfungsi pada mobile, tablet, dan desktop.
- Form kontak/tautan WhatsApp hanya memakai alamat/nomor yang dikonfigurasi, bukan nomor rekaan.
- Tidak ada data sekolah resmi fiktif yang ditampilkan sebagai fakta.

### 13.2 CMS
- Guru/Staff dapat membuat draft sesuai permission.
- Guru/Staff tidak dapat memublikasikan konten secara langsung.
- Admin dapat review, memberi catatan, mengembalikan untuk revisi, dan memublikasikan.
- Perubahan konten terpublikasi harus melalui review kembali.
- Perubahan penting memiliki audit trail.

### 13.3 PPDB
- Admin dapat mengatur periode, program, biaya, dan persyaratan dokumen.
- Pendaftaran hanya dapat memilih satu jenjang dan satu program.
- Sistem mencegah pendaftaran ganda sesuai aturan yang disetujui.
- Nomor pendaftaran unik dan kode akses rahasia tidak disimpan dalam bentuk teks biasa.
- Calon siswa dapat memeriksa status menggunakan kredensial status yang benar.
- Pendaftar tidak dapat mengakses data pendaftar lain dengan mengubah URL atau parameter.
- Dokumen yang dikembalikan memiliki catatan koreksi dan dapat diunggah ulang.
- Pembayaran otomatis hanya dikonfirmasi dari bukti server/provider yang valid; bukti upload tidak otomatis melunasi pembayaran.
- Pembayaran manual memerlukan tindakan role berwenang dan audit log.
- Hasil seleksi hanya dapat ditetapkan role yang berwenang.
- Pemulihan kode akses membatalkan kode lama dan tidak mengungkap kode lama.

### 13.4 Keamanan dan kualitas
- Endpoint sensitif dilindungi autentikasi/otorisasi/rate limit yang sesuai.
- Validasi server mencakup field wajib, file, dan status workflow.
- Tidak ada secret dalam Git atau `.env.example`.
- Test penting mencakup authorization, workflow CMS, pendaftaran, akses status, dokumen, pembayaran, dan hasil.
- README memuat setup lokal, konfigurasi database, migration/seed yang aman, cara menjalankan aplikasi, dan cara menjalankan test.
- Semua tes yang diklaim lulus benar-benar dijalankan; kegagalan atau keterbatasan dilaporkan.

---

## 14. Informasi yang Masih Harus Dikonfirmasi

Jangan menganggap daftar berikut sudah diputuskan. Gunakan konfigurasi atau placeholder sampai pihak sekolah memberikan jawaban.

1. Nama resmi yayasan dan nama resmi setiap sekolah/jenjang.
2. Logo, palet warna resmi, font, aset visual, dan pedoman merek.
3. Visi dan misi resmi.
4. Alamat lengkap, nomor WhatsApp, email, media sosial, jam layanan, dan kontak resmi.
5. Nama kepala sekolah, guru/staf yang boleh ditampilkan, foto, biografi, dan persetujuan publikasi.
6. Deskripsi resmi program/jurusan dan fasilitas.
7. Daftar kegiatan ekstrakurikuler dan prestasi yang dapat diverifikasi.
8. Field formulir PPDB final.
9. Dokumen wajib per jenjang/periode.
10. Nominal biaya pendaftaran dan aturan pembayaran.
11. Provider/metode pembayaran, batas waktu, kebijakan gagal/kedaluwarsa, dan refund.
12. Jadwal periode PPDB dan tanggal pengumuman resmi.
13. Definisi status seleksi serta siapa yang berwenang menetapkan hasil akhir.
14. Retensi/penghapusan data calon siswa dan dokumen.
15. Detail prosedur verifikasi identitas untuk pemulihan kode akses.
16. Kebijakan apakah Petugas PPDB dibuat sebagai role tersendiri atau permission khusus.
17. Detail fitur Dashboard Siswa pada versi awal.

---

## 15. Panduan Mulai untuk OpenCode

Saat PRD ini diberikan kepada OpenCode CLI:

1. Audit repo dan laporkan kondisi proyek serta kompatibilitas stack terlebih dahulu.
2. Buat `docs/PROJECT_AUDIT.md` yang mencatat kondisi awal, tanpa rahasia.
3. Buat `docs/FLOWCHART.md` untuk alur CMS, PPDB, pembayaran, dan login.
4. Buat `docs/ERD.md` dengan ERD Mermaid, atribut, PK/FK, kardinalitas, constraint, dan alasan entitas.
5. Buat `docs/IMPLEMENTATION_PLAN.md` berisi fase, dependensi, file utama yang akan dibuat, serta rencana pengujian.
6. Tampilkan rancangan flowchart dan ERD kepada pengguna sebelum membuat migration final. Jangan melewati persetujuan ERD kecuali pengguna secara eksplisit meminta untuk melanjutkan tanpa menunggu.
7. Setelah rancangan disetujui, implementasikan bertahap mulai dari fondasi dan hak akses, lalu CMS, lalu PPDB, lalu dashboard/notifikasi, kemudian QA.
8. Buat `README.md` yang sesuai dengan lingkungan Windows/Laragon dan versi proyek aktual.
9. Jalankan pemeriksaan yang tersedia, misalnya `php artisan test`, formatter/linter yang sudah terpasang, dan build frontend. Jangan memasang dependency baru tanpa alasan.
10. Pada setiap laporan, tulis: **selesai**, **belum selesai**, **tes yang dijalankan dan hasilnya**, **risiko/keputusan yang masih terbuka**, serta **langkah berikutnya**.

### Prompt pembuka yang dapat dipakai

> Baca `PRD_Website_DMR_OpenCode.md` secara menyeluruh. Jangan langsung coding. Audit repo dan lingkungan terlebih dahulu, lalu buat `docs/PROJECT_AUDIT.md`, `docs/FLOWCHART.md`, `docs/ERD.md`, dan `docs/IMPLEMENTATION_PLAN.md`. Ikuti seluruh aturan dalam PRD: jangan mengarang data resmi, jangan menambah fitur di luar scope, jangan menghapus/menimpa file tanpa alasan, dan jangan membuat migration final sebelum rancangan ERD ditinjau. Setelah dokumen awal selesai, tampilkan ringkasan kondisi proyek, flowchart, ERD, keputusan yang masih terbuka, dan rencana implementasi untuk ditinjau.

---

## 16. Prinsip Penutup

Prioritaskan keamanan, kejelasan alur, kualitas UI/UX, kemudahan pengelolaan, dan kesesuaian dengan kebutuhan yang sudah ditentukan. Jika kebutuhan resmi belum tersedia, tandai sebagai **belum dikonfirmasi** alih-alih mengarang. Implementasi harus bertahap, dapat diuji, dan mudah dipelihara.
