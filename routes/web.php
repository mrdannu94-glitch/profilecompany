<?php

use App\Http\Controllers\Admin\PpdbDocumentController;
use App\Http\Controllers\Admin\PpdbPaymentController;
use App\Http\Controllers\Admin\PpdbPeriodController;
use App\Http\Controllers\Admin\PpdbRegistrationController;
use App\Http\Controllers\Admin\PpdbRequirementController;
use App\Http\Controllers\Admin\PpdbSelectionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PlaceholderController;
use App\Http\Controllers\Public\PpdbInfoController;
use App\Http\Controllers\Public\RegistrationController;
use App\Http\Controllers\Public\RegistrationDocumentController;
use App\Http\Controllers\Public\StatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website Publik
|--------------------------------------------------------------------------
|
| Halaman publik belum dibangun bertahap sesuai rencana implementasi. Route
| di bawah ini tetap dapat diakses agar navigasi tidak rusak, dan diganti
| satu per satu pada fase berikutnya.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/profil', [PlaceholderController::class, 'show'])
    ->defaults('title', 'Profil Sekolah')
    ->defaults('phase', 'Fase 3')
    ->name('profil');

Route::get('/visi-misi', [PlaceholderController::class, 'show'])
    ->defaults('title', 'Visi & Misi')
    ->defaults('phase', 'Fase 3')
    ->name('visi-misi');

Route::get('/program', [PlaceholderController::class, 'show'])
    ->defaults('title', 'Jurusan & Program')
    ->defaults('phase', 'Fase 3')
    ->name('program');

Route::get('/berita', [PlaceholderController::class, 'show'])
    ->defaults('title', 'Berita & Pengumuman')
    ->defaults('phase', 'Fase 3')
    ->name('berita');

Route::get('/ppdb', [PpdbInfoController::class, 'show'])->name('ppdb.info');

Route::get('/kontak', [PlaceholderController::class, 'show'])
    ->defaults('title', 'Kontak')
    ->defaults('phase', 'Fase 3')
    ->name('kontak');

/*
|--------------------------------------------------------------------------
| PPDB Online
|--------------------------------------------------------------------------
|
| Calon siswa tidak memakai akun internal. Pendaftaran memakai formulir,
| sedangkan pemeriksaan status memakai nomor pendaftaran dan kode akses
| rahasia yang hanya ditampilkan satu kali.
|
*/

Route::get('/ppdb/daftar', [RegistrationController::class, 'create'])->name('ppdb.register');

Route::post('/ppdb/daftar', [RegistrationController::class, 'store'])
    ->middleware('throttle:ppdb-registration')
    ->name('ppdb.register.store');

Route::get('/ppdb/status', [StatusController::class, 'create'])->name('ppdb.status');

Route::post('/ppdb/status', [StatusController::class, 'store'])
    ->middleware('throttle:ppdb-status')
    ->name('ppdb.status.check');

Route::middleware('registration.access')->group(function () {
    Route::get('/ppdb/status/pendaftaran', [StatusController::class, 'show'])->name('ppdb.status.show');

    Route::post('/ppdb/status/dokumen', [RegistrationDocumentController::class, 'store'])
        ->name('ppdb.documents.store');

    Route::get('/ppdb/status/dokumen/{document}/unduh', [RegistrationDocumentController::class, 'download'])
        ->name('ppdb.documents.download');
});

/*
|--------------------------------------------------------------------------
| Autentikasi Internal
|--------------------------------------------------------------------------
|
| Satu halaman login untuk seluruh role internal. Calon siswa tidak memakai
| akun; mereka memeriksa status memakai nomor pendaftaran dan kode akses.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Portal Internal
|--------------------------------------------------------------------------
|
| Setiap dasbor role dijaga middleware role. Otorisasi konten yang lebih
| rinci dilengkapi pada fase berikutnya melalui policy dan permission.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/admin', [DashboardController::class, 'show'])
        ->defaults('role', 'admin')
        ->middleware('role:admin')
        ->name('dashboard.admin');

    Route::get('/dashboard/cs-admin', [DashboardController::class, 'show'])
        ->defaults('role', 'cs_admin')
        ->middleware('role:cs_admin')
        ->name('dashboard.cs_admin');

    Route::get('/dashboard/ppdb-officer', [DashboardController::class, 'show'])
        ->defaults('role', 'ppdb_officer')
        ->middleware('role:ppdb_officer')
        ->name('dashboard.ppdb_officer');

    Route::get('/dashboard/guru', [DashboardController::class, 'show'])
        ->defaults('role', 'teacher')
        ->middleware('role:teacher')
        ->name('dashboard.teacher');

    Route::get('/dashboard/staff', [DashboardController::class, 'show'])
        ->defaults('role', 'staff')
        ->middleware('role:staff')
        ->name('dashboard.staff');

    Route::get('/dashboard/siswa', [DashboardController::class, 'show'])
        ->defaults('role', 'student')
        ->middleware('role:student')
        ->name('dashboard.student');
});

/*
|--------------------------------------------------------------------------
| Portal Internal PPDB
|--------------------------------------------------------------------------
|
| Modul PPDB dijaga middleware permission. Setiap aksi sensitif juga
| diperiksa policy pada controller sehingga hak akses tidak hanya
| bergantung pada route.
|
*/

Route::middleware(['auth', 'permission:manage_periods'])->group(function () {
    Route::get('/portal/ppdb/periode', [PpdbPeriodController::class, 'index'])->name('admin.ppdb.periods.index');
    Route::get('/portal/ppdb/periode/tambah', [PpdbPeriodController::class, 'create'])->name('admin.ppdb.periods.create');
    Route::post('/portal/ppdb/periode', [PpdbPeriodController::class, 'store'])->name('admin.ppdb.periods.store');
    Route::get('/portal/ppdb/periode/{period}/ubah', [PpdbPeriodController::class, 'edit'])->name('admin.ppdb.periods.edit');
    Route::put('/portal/ppdb/periode/{period}', [PpdbPeriodController::class, 'update'])->name('admin.ppdb.periods.update');
});

Route::middleware(['auth', 'permission:manage_requirements'])->group(function () {
    Route::get('/portal/ppdb/persyaratan', [PpdbRequirementController::class, 'index'])->name('admin.ppdb.requirements.index');
    Route::post('/portal/ppdb/persyaratan', [PpdbRequirementController::class, 'store'])->name('admin.ppdb.requirements.store');
    Route::put('/portal/ppdb/persyaratan/{requirement}', [PpdbRequirementController::class, 'update'])->name('admin.ppdb.requirements.update');
    Route::delete('/portal/ppdb/persyaratan/{requirement}', [PpdbRequirementController::class, 'destroy'])->name('admin.ppdb.requirements.destroy');
});

Route::middleware(['auth', 'permission:verify_ppdb_documents'])->group(function () {
    Route::get('/portal/ppdb/dokumen', [PpdbDocumentController::class, 'index'])->name('admin.ppdb.documents.index');
    Route::post('/portal/ppdb/dokumen/{document}/periksa', [PpdbDocumentController::class, 'review'])->name('admin.ppdb.documents.review');
    Route::get('/portal/ppdb/dokumen/{document}/unduh', [PpdbDocumentController::class, 'download'])->name('admin.ppdb.documents.download');
});

Route::middleware(['auth', 'permission:verify_ppdb_payment_manual'])->group(function () {
    Route::get('/portal/ppdb/pembayaran', [PpdbPaymentController::class, 'index'])->name('admin.ppdb.payments.index');
    Route::post('/portal/ppdb/pembayaran/{payment}/konfirmasi', [PpdbPaymentController::class, 'confirm'])->name('admin.ppdb.payments.confirm');
    Route::post('/portal/ppdb/pembayaran/{payment}/gagal', [PpdbPaymentController::class, 'fail'])->name('admin.ppdb.payments.fail');
});

Route::middleware(['auth', 'permission:set_selection_result'])->group(function () {
    Route::post('/portal/ppdb/pendaftaran/{registration}/hasil', [PpdbSelectionController::class, 'store'])->name('admin.ppdb.selection.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/portal/ppdb/pendaftaran', [PpdbRegistrationController::class, 'index'])
        ->middleware('permission:manage_periods,verify_ppdb_documents,verify_ppdb_payment_manual,set_selection_result')
        ->name('admin.ppdb.registrations.index');

    Route::get('/portal/ppdb/pendaftaran/{registration}', [PpdbRegistrationController::class, 'show'])
        ->middleware('permission:manage_periods,verify_ppdb_documents,verify_ppdb_payment_manual,set_selection_result')
        ->name('admin.ppdb.registrations.show');

    Route::post('/portal/ppdb/pendaftaran/{registration}/kode-akses', [PpdbRegistrationController::class, 'recoverAccessCode'])
        ->middleware('permission:reset_access_code')
        ->name('admin.ppdb.registrations.access-code');
});
