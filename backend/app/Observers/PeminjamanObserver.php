<?php

namespace App\Observers;

use App\Models\Peminjaman;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class PeminjamanObserver
{
    public function created(Peminjaman $peminjaman)
    {
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Membuat transaksi peminjaman baru (ID: #TRX-{$peminjaman->id})"]);
    }

    public function updated(Peminjaman $peminjaman)
    {
        $perubahan = $peminjaman->getChanges();
        unset($perubahan['updated_at']);
        
        // Cek khusus jika ini adalah aktivitas pengembalian atau persetujuan (ubah status)
        if (isset($perubahan['status'])) {
            $statusLama = $peminjaman->getOriginal('status');
            $statusBaru = $perubahan['status'];
            
            if ($statusBaru === 'dikembalikan') {
                LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Menerima PENGEMBALIAN alat untuk transaksi #TRX-{$peminjaman->id}"]);
                return; // Stop di sini agar tidak dobel log
            }
            
            if ($statusBaru === 'dipinjam') {
                LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "MENYETUJUI peminjaman alat untuk transaksi #TRX-{$peminjaman->id}"]);
                return;
            }
        }
        
        // Jika perubahan biasa selain status
        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $peminjaman->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }
        
        $teks = implode(', ', $detail);
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Memperbarui transaksi #TRX-{$peminjaman->id}. Detail: {$teks}"]);
    }

    public function deleted(Peminjaman $peminjaman)
    {
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Menghapus data transaksi peminjaman (ID: #TRX-{$peminjaman->id})"]);
    }
}