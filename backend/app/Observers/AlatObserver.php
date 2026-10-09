<?php

namespace App\Observers;

use App\Models\Alat;
use App\Models\LogAktivitas;

class AlatObserver
{
    public function created(Alat $alat): void
    {
        LogAktivitas::catat("Menambahkan alat baru: '{$alat->nama_alat}'");
    }

    public function updated(Alat $alat): void
    {
        $perubahan = $alat->getChanges();
        unset($perubahan['updated_at']);

        if (empty($perubahan)) {
            return;
        }

        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $alat->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }

        LogAktivitas::catat("Memperbarui data alat '{$alat->nama_alat}'. Detail: " . implode(', ', $detail));
    }

    public function deleted(Alat $alat): void
    {
        LogAktivitas::catat("Menghapus data alat: '{$alat->nama_alat}'");
    }
}