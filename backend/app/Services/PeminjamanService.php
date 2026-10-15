<?php

namespace App\Services;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya tempat yang boleh mengubah stok alat akibat transaksi peminjaman.
 *  - Stok BERKURANG hanya saat disetujui (diajukan -> dipinjam).
 *  - Stok BERTAMBAH hanya saat dikembalikan (-> dikembalikan) atau saat transaksi
 *    yang alatnya masih di tangan peminjam dihapus admin.
 *  - Menolak pengajuan TIDAK menyentuh stok.
 */
class PeminjamanService
{
    public const DENDA_PER_HARI = 1000;
    public const MAKS_DENDA_KERUSAKAN = 10000000; // Rp 10 juta
    public const STATUS_BISA_DIKEMBALIKAN = ['dipinjam', 'telat', 'menunggu_pengembalian'];

    public function setujui(int $id): Peminjaman
    {
        return DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::with(['detailPinjam', 'user'])->lockForUpdate()->findOrFail($id);

            if ($peminjaman->status !== 'diajukan') {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}', hanya status 'diajukan' yang bisa disetujui.");
            }

            if ($peminjaman->user && !$peminjaman->user->is_active) {
                throw new Exception('Akun peminjam sudah dinonaktifkan sehingga pengajuan ini tidak bisa disetujui. Tolak saja pengajuan ini.');
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

    /**
     * Hapus transaksi permanen. Transaksi yang alatnya masih di tangan peminjam
     * (dipinjam / telat / menunggu pengembalian) tidak boleh dihapus: proses
     * pengembaliannya dulu. Status dibaca setelah baris dikunci, jadi aman dari
     * proses bersamaan.
     *
     * @throws \DomainException bila transaksi masih dipinjam
     */
    public function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            $peminjaman = Peminjaman::lockForUpdate()->findOrFail($id);

            if (in_array($peminjaman->status, self::STATUS_BISA_DIKEMBALIKAN, true)) {
                $status = str_replace('_', ' ', $peminjaman->status);
                throw new \DomainException("Transaksi ini masih berstatus '{$status}' (alat belum kembali) sehingga tidak bisa dihapus. Proses pengembaliannya terlebih dahulu.");
            }

            $peminjaman->detailPinjam()->delete();
            $peminjaman->delete();
        });
    }

    public function hitungHariTelat($tglKembaliPlan): int
    {
        $tglPlan = Carbon::parse($tglKembaliPlan)->startOfDay();
        $hariIni = Carbon::today();

        return $hariIni->greaterThan($tglPlan) ? (int) $tglPlan->diffInDays($hariIni) : 0;
    }

    /**
     * @param array<int,int|string|null> $tidakLayak  [detail_pinjam.id => jumlah unit yang rusak berat / hilang].
     *        Unit ini TIDAK dikembalikan ke stok (supaya tidak bisa dipinjam lagi),
     *        dan dicatat pada kondisi_kembali. Kosong = semua unit layak (perilaku lama).
     */
    public function terimaPengembalian(int $id, string $kondisiKembali, $dendaKerusakan, int $petugasId, array $tidakLayak = []): array
    {
        return DB::transaction(function () use ($id, $kondisiKembali, $dendaKerusakan, $petugasId, $tidakLayak) {
            $peminjaman = Peminjaman::with('detailPinjam.alat')->lockForUpdate()->findOrFail($id);

            if (!in_array($peminjaman->status, self::STATUS_BISA_DIKEMBALIKAN, true)) {
                throw new Exception("Peminjaman ini berstatus '{$peminjaman->status}' dan tidak bisa diproses sebagai pengembalian.");
            }

            // Susun jumlah unit tidak layak per baris detail + validasi terhadap data transaksi
            $unitRusak = [];
            $catatan = [];
            foreach ($tidakLayak as $detailId => $jumlah) {
                $jumlah = (int) $jumlah;
                if ($jumlah <= 0) {
                    continue;
                }

                $detail = $peminjaman->detailPinjam->firstWhere('id', (int) $detailId);
                if (!$detail) {
                    throw new Exception('Data alat tidak layak tidak sesuai dengan transaksi ini.');
                }
                if ($jumlah > $detail->jumlah) {
                    $nama = $detail->alat->nama_alat ?? 'alat';
                    throw new Exception("Jumlah tidak layak untuk '{$nama}' ({$jumlah}) melebihi jumlah yang dipinjam ({$detail->jumlah}).");
                }

                $unitRusak[$detail->id] = $jumlah;
                $catatan[] = ($detail->alat->nama_alat ?? 'Alat') . " ({$jumlah} unit)";
            }

            // Catatan ditempel ke kondisi_kembali; kolomnya hanya 255 karakter
            if (!empty($catatan)) {
                $akhiran = ' | TIDAK LAYAK (tidak masuk stok): ' . implode(', ', $catatan);
                $kondisiKembali = Str::limit($kondisiKembali, max(0, 255 - mb_strlen($akhiran)), '') . $akhiran;
                $kondisiKembali = mb_substr($kondisiKembali, 0, 255);
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

            $unitTidakLayak = 0;
            foreach ($peminjaman->detailPinjam as $detail) {
                $rusak   = $unitRusak[$detail->id] ?? 0;
                $kembali = $detail->jumlah - $rusak;
                $unitTidakLayak += $rusak;

                if ($kembali > 0) {
                    $alat = Alat::lockForUpdate()->find($detail->alat_id);
                    if ($alat) {
                        $alat->increment('stok', $kembali);
                    }
                }
            }

            return [
                'pengembalian'     => $pengembalian,
                'hari_telat'       => $hariTelat,
                'denda_telat'      => $dendaTelat,
                'denda_total'      => $dendaTotal,
                'unit_tidak_layak' => $unitTidakLayak,
            ];
        });
    }
}