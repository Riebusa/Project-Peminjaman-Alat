<?php

namespace App\Observers;

use App\Models\Kategori;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class KategoriObserver
{
    public function created(Kategori $kategori)
{
        if (auth()->check()) {
            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => "Menambahkan kategori baru: '{$kategori->nama_kategori}'"
            ]);
        }
    }

    // public function created(Kategori $kategori)
    // {
    //     LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Menambahkan kategori baru: '{$kategori->nama_kategori}'"]);
    // }

    public function updated(Kategori $kategori)
    {
        $perubahan = $kategori->getChanges();
        unset($perubahan['updated_at']);
        
        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $kategori->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }
        
        $teks = implode(', ', $detail);
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Memperbarui kategori ID #{$kategori->id}. Detail: {$teks}"]);
    }

    public function deleted(Kategori $kategori)
    {
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Menghapus kategori: '{$kategori->nama_kategori}'"]);
    }
}