<?php

namespace App\Observers;

use App\Models\Alat;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class AlatObserver
{
    // Fungsi bantuan untuk menyimpan log
    private function catatLog($teksAktivitas)
    {
        if (Auth::check()) {
            LogAktivitas::create([
                'user_id'   => Auth::id(),
                'aktivitas' => $teksAktivitas,
            ]);
        }
    }

    public function created(Alat $alat)
    {
        $this->catatLog("Menambahkan data alat baru: '{$alat->nama_alat}'");
    }

    public function updated(Alat $alat)
    {
        $perubahan = $alat->getChanges();
        unset($perubahan['updated_at']); // Abaikan perubahan waktu agar log tidak penuh
        
        if (!empty($perubahan)) {
            $detail = [];
            foreach ($perubahan as $kolom => $nilaiBaru) {
                // Mengambil nilai lama sebelum disimpan
                $nilaiLama = $alat->getOriginal($kolom) ?? 'kosong';
                
                // Menyusun format nilai lama dan baru
                $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
            }
            
            $teks = implode(', ', $detail);
            
            // Format diubah menggunakan ". Detail: " agar file Blade mendeteksinya dan memunculkan kotak
            $this->catatLog("Memperbarui data alat '{$alat->nama_alat}'. Detail: {$teks}");
        }
    }

    public function deleted(Alat $alat)
    {
        $this->catatLog("Menghapus data alat: '{$alat->nama_alat}'");
    }
}