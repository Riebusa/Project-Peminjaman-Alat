<?php

namespace App\Observers;

use App\Models\LogAktivitas;
use App\Models\Peminjaman;

class PeminjamanObserver
{
    public function created(Peminjaman $peminjaman): void
    {
        LogAktivitas::catat("Membuat peminjaman baru #TRX-{$peminjaman->id}");
    }

    public function updated(Peminjaman $peminjaman): void
    {
        $perubahan = $peminjaman->getChanges();
        unset($perubahan['updated_at']);

        if (empty($perubahan)) {
            return;
        }

        if (isset($perubahan['status'])) {
            $lama = $peminjaman->getOriginal('status');
            $baru = $perubahan['status'];
            $id   = "#TRX-{$peminjaman->id}";

            $pesan = match (true) {
                $lama === 'diajukan' && $baru === 'dipinjam'
                    => "MENYETUJUI peminjaman alat untuk transaksi {$id}",
                $lama === 'diajukan' && $baru === 'ditolak'
                    => "MENOLAK pengajuan peminjaman untuk transaksi {$id}",
                $baru === 'menunggu_pengembalian'
                    => "Peminjam MENGAJUKAN pengembalian alat untuk transaksi {$id}",
                $lama === 'menunggu_pengembalian' && in_array($baru, ['dipinjam', 'telat'], true)
                    => "MENOLAK request pengembalian untuk transaksi {$id} (status kembali ke {$baru})",
                $baru === 'dikembalikan'
                    => $this->pesanPengembalian($peminjaman, $id),
                default => null,
            };

            if ($pesan !== null) {
                LogAktivitas::catat($pesan);
                return;
            }
        }

        // Perubahan lain (atau status di luar yang dikenali)
        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $peminjaman->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }

        LogAktivitas::catat("Memperbarui transaksi #TRX-{$peminjaman->id}. Detail: " . implode(', ', $detail));
    }

    public function deleted(Peminjaman $peminjaman): void
    {
        LogAktivitas::catat("Menghapus data transaksi peminjaman #TRX-{$peminjaman->id}");
    }

    private function pesanPengembalian(Peminjaman $peminjaman, string $id): string
    {
        $pesan = "Menerima PENGEMBALIAN alat untuk transaksi {$id}";

        // Pengembalian dibuat service sebelum status diubah, jadi sudah ada di sini
        $pengembalian = $peminjaman->pengembalian;
        if ($pengembalian) {
            $pesan .= ' (denda Rp ' . number_format($pengembalian->denda, 0, ',', '.') . ')';
        }

        return $pesan;
    }
}