<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Import Model
use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Peminjaman;

// Import Observer
use App\Observers\AlatObserver;
use App\Observers\KategoriObserver;
use App\Observers\UserObserver;
use App\Observers\PeminjamanObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Daftarkan semua Observer di sini
        Alat::observe(AlatObserver::class);
        Kategori::observe(KategoriObserver::class);
        User::observe(UserObserver::class);
        Peminjaman::observe(PeminjamanObserver::class);
    }
}