# Matriks Status dan Transisi

**Status dokumen:** kanonik
**Terakhir diperbarui:** 19 Oktober 2026
**Acuan:** PRD bab 8.2, 8.5, 8.6, 8.7; `docs/ERD.md`; `docs/FLOWCHART.md`

Dokumen ini adalah satu-satunya sumber kosakata status yang tersimpan di
database. Semua nilai di bawah sudah tersedia sebagai enum di `app/Enums/`
dan dipakai melalui cast pada model terkait, sehingga nilai di luar daftar
akan ditolak saat penulisan.

---

## 1. Ringkasan

| Entitas | Kolom status | Enum |
|---|---|---|
| `ppdb_periods` | `status` | `App\Enums\PpdbPeriodStatus` |
| `registrations` | `status` | `App\Enums\RegistrationStatus` |
| `registration_documents` | `status` | `App\Enums\DocumentStatus` |
| `payments` | `status` | `App\Enums\PaymentStatus` |
| `payments` | `verification_method` | `App\Enums\VerificationMethod` |
| `payment_events` | `processing_status` | `App\Enums\PaymentEventStatus` |
| `selection_decisions` | `decision_status`, `previous_status` | `App\Enums\SelectionDecisionStatus` |
| `media` | `storage_visibility` | `App\Enums\StorageVisibility` |

---

## 2. `ppdb_periods.status`

| Nilai | Label | Arti |
|---|---|---|
| `draft` | Draf | Periode disusun, belum menerima pendaftaran |
| `scheduled` | Terjadwal | Tanggal sudah ditentukan, pendaftaran belum dibuka |
| `open` | Dibuka | Menerima pendaftaran baru |
| `closed` | Ditutup | Pendaftaran ditutup, hasil belum diumumkan |
| `announced` | Diumumkan | Hasil sudah diumumkan |

Transisi: `draft → scheduled → open → closed → announced`. Hanya periode
berstatus `open` yang menerima pendaftaran baru.

---

## 3. `registrations.status`

| Nilai | Label | Arti |
|---|---|---|
| `draft` | Draf | Formulir belum dikirim; nomor pendaftaran dan kode akses belum terbit |
| `submitted` | Terkirim | Formulir terkirim, menunggu pembayaran |
| `payment_pending` | Menunggu Pembayaran | Pembayaran belum dikonfirmasi |
| `documents_pending` | Menunggu Dokumen | Dokumen sedang diperiksa petugas |
| `documents_approved` | Dokumen Disetujui | Seluruh dokumen disetujui |
| `documents_rejected` | Dokumen Dikembalikan | Ada dokumen yang dikembalikan untuk diperbaiki |
| `completed` | Selesai | Pendaftaran selesai diproses |
| `expired` | Kedaluwarsa | Pendaftaran tidak diteruskan |

Status pembayaran, dokumen, dan hasil seleksi tetap dicatat terpisah pada
tabelnya masing-masing. Status pendaftaran hanya merangkum tahap.

---

## 4. `registration_documents.status`

| Nilai | Label | Arti |
|---|---|---|
| `uploaded` | Terunggah | Dokumen baru diunggah, belum diperiksa |
| `under_review` | Sedang Diperiksa | Petugas sedang memeriksa |
| `approved` | Disetujui | Dokumen memenuhi syarat |
| `needs_revision` | Perlu Diperbaiki | Dikembalikan dengan catatan koreksi |
| `rejected` | Ditolak | Ditolak tanpa perbaikan |

Status `missing` **tidak disimpan sebagai baris**. Status tersebut berarti
belum ada unggahan untuk persyaratan terkait dan dihitung dari selisih
persyaratan wajib dengan dokumen yang ada.

---

## 5. `payments.status` dan `payments.verification_method`

| Nilai | Label | Arti |
|---|---|---|
| `pending_payment` | Menunggu Pembayaran | Instruksi pembayaran sudah diberikan, dana belum masuk |
| `pending_verification` | Menunggu Verifikasi | Provider melaporkan pembayaran, menunggu verifikasi server |
| `confirmed` | Terkonfirmasi | Pembayaran sah dan tercatat |
| `manual_review` | Pemeriksaan Manual | Perlu diperiksa petugas |
| `failed` | Gagal | Percobaan pembayaran gagal |
| `expired` | Kedaluwarsa | Melewati batas waktu yang dikonfigurasi |

`verification_method`: `automatic` (diverifikasi provider) atau `manual`
( diverifikasi petugas berwenang dengan catatan audit).

Transisi: `pending_payment → pending_verification → confirmed`, atau
`pending_payment → manual_review → confirmed | failed`, dan setiap percobaan
dapat berakhir `failed` atau `expired`.

> Daftar ini mengikuti PRD bab 8.6 dan menggantikan daftar lama pada ERD
> (`pending`, `paid`, `cancelled`, `requires_review`, `refunded`) supaya
> hanya ada satu kosakata status pembayaran.

---

## 6. `payment_events.processing_status`

| Nilai | Label |
|---|---|
| `received` | Diterima |
| `processed` | Diproses |
| `ignored` | Diabaikan |
| `failed` | Gagal Diproses |

`event_type` bukan daftar tertutup: kolom ini menyimpan nama event sesuai
provider (misalnya `payment.confirmed`).

---

## 7. `selection_decisions.decision_status`

| Nilai | Label | Arti |
|---|---|---|
| `pending` | Menunggu Keputusan | Belum ada keputusan final |
| `accepted` | Diterima | Lolos seleksi |
| `not_accepted` | Tidak Diterima | Tidak lolos seleksi |
| `waitlisted` | Daftar Tunggu | Menunggu kekosongan |

Hanya satu keputusan per pendaftaran yang berlaku pada satu waktu
(`is_current = true`). Status sebelum perubahan disimpan pada
`previous_status` sesuai PRD bab 8.7.

---

## 8. `media.storage_visibility`

| Nilai | Arti |
|---|---|
| `public` | Dapat disajikan lewat CDN/URL publik (aset konten) |
| `private` | Hanya lewat endpoint yang memeriksa otorisasi (dokumen PPDB) |

---

## 9. Aturan yang berlaku lintas tabel

1. Status pembayaran tidak mengubah status dokumen maupun keputusan seleksi.
2. Hanya permission `set_selection_result` yang boleh menetapkan hasil akhir.
3. Perubahan status yang bersifat final (pembayaran terkonfirmasi, dokumen
   disetujui, keputusan ditetapkan) wajib mencatat pelaku dan waktu pada
   audit log tanpa menyimpan rahasia.
4. Nilai status di luar daftar di atas ditolak oleh cast enum.
