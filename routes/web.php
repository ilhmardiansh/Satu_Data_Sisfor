<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\Panel\CategoryController;
use App\Http\Controllers\Panel\CollectedDataController;
use App\Http\Controllers\Panel\ContentController;
use App\Http\Controllers\Panel\DataFormController;
use App\Http\Controllers\Panel\DataRequestController;
use App\Http\Controllers\Panel\FormSubmissionController;
use App\Http\Controllers\Panel\ManageDatasetController;
use App\Http\Controllers\Panel\RingkasanController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\Panel\VisualizationController;
use App\Http\Controllers\PengisianDataController;
use App\Http\Controllers\TentangController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

// Language switcher (ID / EN)
Route::post('/lang/{locale}', function (string $locale): RedirectResponse {
    session(['lang' => in_array($locale, ['id', 'en'], true) ? $locale : 'id']);

    return back();
})->name('lang.switch');

// Public pages
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset');
Route::get('/dataset/{dataset:code}/preview', [DatasetController::class, 'preview'])->name('dataset.preview');
Route::get('/dataset/{dataset:code}/download/{format}', [DatasetController::class, 'download'])->name('dataset.download');
Route::post('/dataset/{dataset:code}/request', [DatasetController::class, 'storeRequest'])->name('dataset.request');
Route::get('/berita', [BeritaController::class, 'index'])->name('berita');
Route::get('/berita/{code}', [BeritaController::class, 'show'])->name('berita.show');
Route::get('/pengisian-data', [PengisianDataController::class, 'index'])->name('pengisian-data');
Route::get('/tentang-kami', [TentangController::class, 'index'])->name('tentang-kami');

// Authentication
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Internal Panel (RBAC)
Route::prefix('panel')->name('panel.')->middleware('auth')->group(function (): void {
    Route::get('/', fn (): RedirectResponse => redirect()->route('panel.ringkasan'));
    Route::get('/ringkasan', [RingkasanController::class, 'index'])->name('ringkasan');

    // Riwayat & File attachment download (all authenticated roles)
    Route::get('/riwayat', [FormSubmissionController::class, 'history'])->name('riwayat');
    Route::get('/submissions/{submission:code}/file/{fieldId}', [FormSubmissionController::class, 'downloadFile'])->name('submissions.file');

    // Produsen Data
    Route::middleware('role:produsen')->group(function (): void {
        Route::get('/isi', [FormSubmissionController::class, 'index'])->name('isi');
        Route::post('/isi/{dataForm:code}', [FormSubmissionController::class, 'store'])->name('isi.submit');
    });

    // Staff: Wali Data & Admin
    Route::middleware('role:wali,admin')->group(function (): void {
        Route::get('/formulir', [DataFormController::class, 'index'])->name('formulir');
        Route::post('/formulir', [DataFormController::class, 'store'])->name('formulir.store');
        Route::put('/formulir/{dataForm:code}', [DataFormController::class, 'update'])->name('formulir.update');
        Route::delete('/formulir/{dataForm:code}', [DataFormController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('formulir.destroy');

        Route::get('/data', [CollectedDataController::class, 'index'])->name('data');
        Route::get('/data/{dataForm:code}/csv', [CollectedDataController::class, 'exportCsv'])->name('data.csv');
        Route::put('/data/submissions/{submission:code}', [CollectedDataController::class, 'updateSubmission'])->name('data.submissions.update');
        Route::delete('/data/submissions/{submission:code}', [CollectedDataController::class, 'destroySubmission'])->name('data.submissions.destroy');

        Route::get('/kategori', [CategoryController::class, 'index'])->name('kategori');
        Route::post('/kategori', [CategoryController::class, 'store'])->name('kategori.store');
        Route::put('/kategori/{category}', [CategoryController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{category}', [CategoryController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('kategori.destroy');

        Route::get('/dataset', [ManageDatasetController::class, 'index'])->name('dataset');
        Route::post('/dataset/from-form', [ManageDatasetController::class, 'storeFromForm'])->name('dataset.from-form');
        Route::patch('/dataset/{dataset:code}/access', [ManageDatasetController::class, 'toggleAccess'])->name('dataset.access');
        Route::put('/dataset/{dataset:code}', [ManageDatasetController::class, 'update'])->name('dataset.update');
        Route::delete('/dataset/{dataset:code}', [ManageDatasetController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('dataset.destroy');

        Route::get('/visualisasi', [VisualizationController::class, 'index'])->name('visualisasi');
        Route::post('/visualisasi', [VisualizationController::class, 'store'])->name('visualisasi.store');
        Route::put('/visualisasi/{visualization:code}', [VisualizationController::class, 'update'])->name('visualisasi.update');
        Route::patch('/visualisasi/{visualization:code}/toggle', [VisualizationController::class, 'toggle'])->name('visualisasi.toggle');
        Route::delete('/visualisasi/{visualization:code}', [VisualizationController::class, 'destroy'])->name('visualisasi.destroy');

        Route::get('/permohonan', [DataRequestController::class, 'index'])->name('permohonan');
        Route::post('/permohonan/{dataRequest:code}/decide', [DataRequestController::class, 'decide'])->name('permohonan.decide');
    });

    // Admin only
    Route::middleware('role:admin')->group(function (): void {
        Route::post('/reset-demo', [RingkasanController::class, 'resetDemo'])->name('reset-demo');

        Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna');
        Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('pengguna.destroy');

        Route::get('/konten', [ContentController::class, 'index'])->name('konten');
        Route::put('/konten/profil', [ContentController::class, 'updateProfil'])->name('konten.profil.update');

        Route::post('/konten/dosen', [ContentController::class, 'storeLecturer'])->name('konten.dosen.store');
        Route::put('/konten/dosen/{lecturer}', [ContentController::class, 'updateLecturer'])->name('konten.dosen.update');
        Route::delete('/konten/dosen/{lecturer}', [ContentController::class, 'destroyLecturer'])->name('konten.dosen.destroy');

        Route::post('/konten/prestasi', [ContentController::class, 'storeAchievement'])->name('konten.prestasi.store');
        Route::put('/konten/prestasi/{achievement}', [ContentController::class, 'updateAchievement'])->name('konten.prestasi.update');
        Route::delete('/konten/prestasi/{achievement}', [ContentController::class, 'destroyAchievement'])->name('konten.prestasi.destroy');

        Route::post('/konten/berita', [ContentController::class, 'storeNews'])->name('konten.berita.store');
        Route::put('/konten/berita/{newsArticle}', [ContentController::class, 'updateNews'])->name('konten.berita.update');
        Route::delete('/konten/berita/{newsArticle}', [ContentController::class, 'destroyNews'])->name('konten.berita.destroy');

        Route::post('/konten/kurikulum', [ContentController::class, 'storeCourse'])->name('konten.kurikulum.store');
        Route::put('/konten/kurikulum/{course}', [ContentController::class, 'updateCourse'])->name('konten.kurikulum.update');
        Route::delete('/konten/kurikulum/{course}', [ContentController::class, 'destroyCourse'])->name('konten.kurikulum.destroy');
    });
});
