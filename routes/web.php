<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasusController;
use App\Http\Controllers\BuktiDigitalController;
use App\Http\Controllers\TindakanForensikController;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return redirect()->route('login');
});

use App\Http\Controllers\AnnouncementController;

// Guest-accessible Announcement routes (intentionally vulnerable: stored XSS / HTML injection)
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Routes for Monitored Sites (previously 'korban')
    Route::prefix('monitored_sites')->name('monitored_sites.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MonitoredSiteController::class, 'index'])->name('index');
        Route::get('/getData', [\App\Http\Controllers\MonitoredSiteController::class, 'getData'])->name('getData');
        Route::post('/', [\App\Http\Controllers\MonitoredSiteController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\MonitoredSiteController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\MonitoredSiteController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\MonitoredSiteController::class, 'destroy'])->name('destroy');
        // Refresh baseline hash for a monitored site
        Route::post('/{id}/refreshBaseline', [\App\Http\Controllers\MonitoredSiteController::class, 'refreshBaseline'])->name('refreshBaseline');
        // Run integrity check for a monitored site (invoke detection service)
        Route::post('/{id}/check', [\App\Http\Controllers\MonitoredSiteController::class, 'checkSite'])->name('checkSite');
    });

    // Routes untuk Modul Kasus
    Route::prefix('kasus')->name('kasus.')->group(function () {
        Route::get('/', [KasusController::class, 'index'])->name('index');
        Route::get('/getData', [KasusController::class, 'getData'])->name('getData');
        Route::get('/{id}', [KasusController::class, 'show'])->name('show');
        Route::get('/{id}/report', [KasusController::class, 'generateReport'])->name('report');
        Route::post('/', [KasusController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [KasusController::class, 'edit'])->name('edit');
        Route::put('/{id}', [KasusController::class, 'update'])->name('update');
        // Resolve incident (separate endpoint)
        Route::post('/{id}/resolve', [KasusController::class, 'resolve'])->name('resolve');
        Route::delete('/{id}', [KasusController::class, 'destroy'])->name('destroy');
        // Analysis endpoint to save manual forensic analysis for a case
        Route::post('/{id}/analysis', [\App\Http\Controllers\TindakanForensikController::class, 'storeAnalysis'])->name('analysis.store');
        // AJAX endpoint to compute diff for arbitrary bukti pairs
        Route::post('/{id}/diff', [KasusController::class, 'diffBukti'])->name('diff.bukti');
    });

    // Routes untuk Modul Bukti Digital
    Route::prefix('bukti-digital')->name('bukti_digital.')->group(function () {
        Route::get('/', [BuktiDigitalController::class, 'index'])->name('index');
        Route::get('/getData', [BuktiDigitalController::class, 'getData'])->name('getData');
        Route::get('/{id}/raw', [BuktiDigitalController::class, 'raw'])->name('raw');
        Route::get('/{id}/download', [BuktiDigitalController::class, 'download'])->name('download');
        Route::post('/', [BuktiDigitalController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [BuktiDigitalController::class, 'edit'])->name('edit');
        Route::post('/{id}', [BuktiDigitalController::class, 'update'])->name('update'); // POST for file upload
        Route::delete('/{id}', [BuktiDigitalController::class, 'destroy'])->name('destroy');
    });

    // Routes untuk Modul Tindakan Forensik
    Route::prefix('tindakan-forensik')->name('tindakan_forensik.')->group(function () {
        Route::get('/', [TindakanForensikController::class, 'index'])->name('index');
        Route::get('/getData', [TindakanForensikController::class, 'getData'])->name('getData');
        Route::post('/', [TindakanForensikController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [TindakanForensikController::class, 'edit'])->name('edit');
        Route::put('/{id}', [TindakanForensikController::class, 'update'])->name('update');
        Route::delete('/{id}', [TindakanForensikController::class, 'destroy'])->name('destroy');
    });

    // Routes untuk Modul User Management (Admin Only)
    Route::prefix('users')->name('users.')->middleware('admin')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/getData', [UserController::class, 'getData'])->name('getData');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});
