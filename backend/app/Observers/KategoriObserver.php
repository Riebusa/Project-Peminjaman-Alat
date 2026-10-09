<?php

namespace App\Observers;

use App\Models\Kategori;
use App\Models\LogAktivitas;

class KategoriObserver
{
    public function created(Kategori $kategori): void
    {
        LogAktivitas::catat("Menambahkan kategori baru: '{$kategori->nama_kategori}'");
    }

    public function updated(Kategori $kategori): void
    {
        $perubahan = $kategori->getChanges();
        unset($perubahan['updated_at']);

        if (empty($perubahan)) {
            return;
        }

        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $kategori->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }

        LogAktivitas::catat("Memperbarui kategori ID #{$kategori->id}. Detail: " . implode(', ', $detail));
    }

    public function deleted(Kategori $kategori): void
    {
        LogAktivitas::catat("Menghapus kategori: '{$kategori->nama_kategori}'");
    }
}