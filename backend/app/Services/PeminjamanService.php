<?php

namespace App\Services;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat yang boleh mengubah stok alat akibat transaksi peminjaman.
 *  - Stok BERKURANG hanya saat disetujui (diajukan -> dipinjam).
 *  - Stok BERTAMBAH hanya saat dikembalikan (-> dikembalikan).
 *  - Menolak pengajuan TIDAK menyentuh stok.
 */
class PeminjamanService
{
    public const DENDA_PER_HARI = 1000;
    public const MAKS_DENDA_KERUSAKAN = 10000000;
    public const STATUS_BISA_DIKEMBALIKAN = ['dipinjam', 'telat', 'menunggu_pengembalian'];

    public function setujui(int $id): Peminjaman
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::with('detailPinjam')->lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'diajukan') {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}', hanya status 'diajukan' yang bisa disetujui.");
            }

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);

                if ($alat->stok < $detail->jumlah) {
                    throw new Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi (tersisa {$alat->stok}, diminta {$detail->jumlah}).");
                }

                $alat->decrement('stok', $detail->jumlah);
            }

            $peminjaman->update(['status' => 'dipinjam']);

            return $peminjaman;
        });
    }

    public function tolak(int $id): Peminjaman
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'diajukan') {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}', hanya status 'diajukan' yang bisa ditolak.");
            }

            $peminjaman->update(['status' => 'ditolak']);

            return $peminjaman;
        });
    }

    /**
     * Tolak request pengembalian dari peminjam: status kembali ke dipinjam
     * atau telat (sesuai tanggal). Stok tidak berubah.
     */
    public function tolakPengembalian(int $id): Peminjaman
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'menunggu_pengembalian') {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}', hanya request pengembalian yang bisa ditolak.");
            }

            $statusBaru = $this->hitungHariTelat($peminjaman->tgl_kembali_plan) > 0 ? 'telat' : 'dipinjam';
            $peminjaman->update(['status' => $statusBaru]);

            return $peminjaman;
        });
    }

    public function hitungHariTelat($tglKembaliPlan): int
    {
        $tglPlan = Carbon::parse($tglKembaliPlan)->startOfDay();
        $hariIni = Carbon::today();

        return $hariIni->greaterThan($tglPlan) ? (int) $tglPlan->diffInDays($hariIni) : 0;
    }

    public function terimaPengembalian(int $id, string $kondisiKembali, $dendaKerusakan, int $petugasId): array
    {
        return DB::transaction(function () use ($id, $kondisiKembali, $dendaKerusakan, $petugasId) {
            $peminjaman = Peminjaman::with('detailPinjam')->lockForUpdate()->findOrFail($id);

            if (!in_array($peminjaman->status, self::STATUS_BISA_DIKEMBALIKAN, true)) {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}' dan tidak bisa diproses sebagai pengembalian.");
            }

            $hariTelat  = $this->hitungHariTelat($peminjaman->tgl_kembali_plan);
            $dendaTelat = $hariTelat * self::DENDA_PER_HARI;
            $dendaTotal = $dendaTelat + (int) ($dendaKerusakan ?? 0);

            $pengembalian = Pengembalian::create([
                'peminjaman_id'   => $peminjaman->id,
                'tgl_kembali'     => now()->toDateString(),
                'kondisi_kembali' => $kondisiKembali,
                'denda'           => $dendaTotal,
                'petugas_id'      => $petugasId,
            ]);

            $peminjaman->update(['status' => 'dikembalikan']);

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->find($detail->alat_id);
                if ($alat) {
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            return [
                'pengembalian' => $pengembalian,
                'hari_telat'   => $hariTelat,
                'denda_telat'  => $dendaTelat,
                'denda_total'  => $dendaTotal,
            ];
        });
    }
}