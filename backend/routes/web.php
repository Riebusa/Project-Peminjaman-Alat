<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\KelolaPeminjamanController;
use App\Http\Controllers\KelolaPengembalianController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PeminjamController;
use App\Http\Controllers\AuthController;

// Halaman Utama / Landing
Route::get('/', function () {
    return view('welcome');
});

// 1. GRUP ADMIN
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard Admin
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // CRUD User
    Route::get('/user', [AdminController::class, 'indexUser'])->name('user.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('user.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('user.store');
    Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('user.edit');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('user.update');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('user.destroy');

    // CRUD Kategori
    Route::get('/kategori', [AdminController::class, 'indexKategori'])->name('kategori.index');
    Route::get('/kategori/create', [AdminController::class, 'createKategori'])->name('kategori.create');
    Route::post('/kategori', [AdminController::class, 'storeKategori'])->name('kategori.store');
    Route::get('/kategori/{id}/edit', [AdminController::class, 'editKategori'])->name('kategori.edit');
    Route::put('/kategori/{id}', [AdminController::class, 'updateKategori'])->name('kategori.update');
    Route::delete('/kategori/{id}', [AdminController::class, 'destroyKategori'])->name('kategori.destroy');

    // CRUD Alat
    Route::get('/alat', [AdminController::class, 'indexAlat'])->name('alat.index');
    Route::get('/alat/create', [AdminController::class, 'createAlat'])->name('alat.create');
    Route::post('/alat', [AdminController::class, 'storeAlat'])->name('alat.store');
    Route::get('/alat/{id}/edit', [AdminController::class, 'editAlat'])->name('alat.edit');
    Route::put('/alat/{id}', [AdminController::class, 'updateAlat'])->name('alat.update');
    Route::delete('/alat/{id}', [AdminController::class, 'destroyAlat'])->name('alat.destroy');

    // --- KELOLA PEMINJAMAN (Barang Keluar & Persetujuan) ---
    Route::prefix('peminjaman')->name('peminjaman.')->group(function () {
        Route::get('/', [KelolaPeminjamanController::class, 'index'])->name('index');
        Route::get('/create', [KelolaPeminjamanController::class, 'create'])->name('create');
        Route::post('/store', [KelolaPeminjamanController::class, 'store'])->name('store');
        Route::post('/{id}/setujui', [KelolaPeminjamanController::class, 'setujui'])->name('setujui');
        Route::post('/{id}/tolak', [KelolaPeminjamanController::class, 'tolak'])->name('tolak');
        Route::delete('/{id}', [KelolaPeminjamanController::class, 'destroy'])->name('destroy');
    });

    // --- KELOLA PENGEMBALIAN (Barang Masuk, Request Peminjam, & Denda) ---
    Route::prefix('pengembalian')->name('pengembalian.')->group(function () {
        Route::get('/', [KelolaPengembalianController::class, 'index'])->name('index');
        Route::get('/create', [KelolaPengembalianController::class, 'create'])->name('create');
        Route::post('/store-manual', [KelolaPengembalianController::class, 'storeManual'])->name('storeManual');
        Route::post('/{id}/terima', [KelolaPengembalianController::class, 'prosesTerima'])->name('terima');
        Route::post('/{id}/tolak', [KelolaPengembalianController::class, 'tolakPengembalian'])->name('tolak');
    });

    // Log Aktivitas
    Route::get('/log-aktivitas', [AdminController::class, 'logAktivitas'])->name('log.index');
});

// 2. GRUP PETUGAS
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [PetugasController::class, 'index'])->name('dashboard');
    
    // TUGAS 1: Menyetujui Peminjaman
    Route::get('/peminjaman', [PetugasController::class, 'indexPeminjaman'])->name('peminjaman.index');
    Route::post('/peminjaman/{id}/setujui', [PetugasController::class, 'setujuiPeminjaman'])->name('peminjaman.setujui');
    
    // TUGAS 2: Kelola Pengembalian
    Route::get('/pengembalian', [PetugasController::class, 'indexPengembalian'])->name('pengembalian.index');
    Route::post('/pengembalian/{id}/proses', [PetugasController::class, 'prosesPengembalian'])->name('pengembalian.proses');

    // TUGAS 3: Cetak Laporan
    Route::get('/laporan', [PetugasController::class, 'laporan'])->name('laporan.index');
});

// 3. GRUP PEMINJAM
Route::middleware(['auth', 'role:peminjam'])->prefix('peminjam')->name('peminjam.')->group(function () {
    Route::get('/katalog', [PeminjamController::class, 'katalogAlat'])->name('katalog');
    Route::post('/ajukan', [PeminjamController::class, 'ajukanPeminjaman'])->name('ajukan');
    Route::get('/riwayat', [PeminjamController::class, 'riwayatPeminjaman'])->name('riwayat');
    Route::post('/kembalikan/{id}', [PeminjamController::class, 'prosesPengembalian'])->name('kembalikan');
    Route::delete('/batalkan/{id}', [PeminjamController::class, 'batalkanPeminjaman'])->name('batalkan');
});

// 4. AUTHENTICATION & LOGOUT
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');