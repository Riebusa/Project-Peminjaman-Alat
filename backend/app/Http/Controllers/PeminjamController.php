<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // Aturan peminjaman (ubah angkanya di sini kalau kebijakan berubah)
    private const MAKS_HARI_PINJAM = 14;
    private const MAKS_PENGAJUAN_MENUNGGU = 3;
    private const KONDISI_BISA_DIPINJAM = 'baik';

    // Jumlah peminjaman milik user yang sudah lewat tenggat dan belum dikembalikan
    private function jumlahTelat(): int
    {
        return Peminjaman::where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat'])
            ->whereDate('tgl_kembali_plan', '<', today())
            ->count();
    }

    // Alasan user tidak boleh mengajukan peminjaman baru (null = boleh)
    private function alasanBlokir(): ?string
    {
        $telat = $this->jumlahTelat();
        if ($telat > 0) {
            return "Anda memiliki {$telat} peminjaman yang melewati tenggat. Kembalikan alatnya lewat menu Riwayat sebelum mengajukan peminjaman baru.";
        }

        $menunggu = Peminjaman::where('user_id', auth()->id())->where('status', 'diajukan')->count();
        if ($menunggu >= self::MAKS_PENGAJUAN_MENUNGGU) {
            return "Anda sudah memiliki {$menunggu} pengajuan yang menunggu persetujuan (maksimal " . self::MAKS_PENGAJUAN_MENUNGGU . "). Tunggu sampai diproses petugas.";
        }

        return null;
    }

    // 1. Melihat daftar/katalog alat
    public function katalogAlat(Request $request)
    {
        $search = $request->input('search');
        $kategoriId = $request->input('kategori');

        $alats = Alat::with('kategori')
            ->whereRaw('LOWER(TRIM(status_kondisi)) = ?', [self::KONDISI_BISA_DIPINJAM])
            ->when($search, fn ($q, $s) => $q->where('nama_alat', 'like', "%{$s}%"))
            ->when($kategoriId, fn ($q, $k) => $q->where('kategori_id', $k))
            // Alat yang masih ada stok tampil lebih dulu, yang habis di belakang
            ->orderByRaw('CASE WHEN stok > 0 THEN 0 ELSE 1 END')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('peminjam.katalog', [
            'alats'        => $alats,
            'search'       => $search,
            'kategoris'    => Kategori::orderBy('nama_kategori')->get(),
            'alasanBlokir' => $this->alasanBlokir(),
            'maksHari'     => self::MAKS_HARI_PINJAM,
        ]);
    }

    // 2. Mengajukan peminjaman baru
    public function ajukanPeminjaman(Request $request)
    {
        $jumlahAlat = is_array($request->alat_id) ? count($request->alat_id) : 0;

        $request->validate([
            'tgl_kembali_plan' => [
                'required', 'date', 'after_or_equal:today',
                'before_or_equal:' . today()->addDays(self::MAKS_HARI_PINJAM)->toDateString(),
            ],
            'alat_id'   => ['required', 'array', 'min:1'],
            'alat_id.*' => ['required', 'integer', 'distinct', 'exists:alat,id'],
            'jumlah'    => ['required', 'array', 'size:' . $jumlahAlat],
            'jumlah.*'  => ['required', 'integer', 'min:1'],
        ], [
            'tgl_kembali_plan.after_or_equal'  => 'Tanggal kembali tidak boleh sebelum hari ini.',
            'tgl_kembali_plan.before_or_equal' => 'Lama peminjaman maksimal ' . self::MAKS_HARI_PINJAM . ' hari.',
            'alat_id.*.exists'                 => 'Alat yang dipilih tidak ditemukan.',
            'alat_id.*.distinct'               => 'Alat yang sama tidak boleh dipilih dua kali.',
            'jumlah.*.integer'                 => 'Jumlah pinjam harus berupa angka bulat.',
            'jumlah.*.min'                     => 'Jumlah pinjam minimal 1.',
        ]);

        // Aturan: tidak boleh mengajukan baru jika ada yang terlambat / pengajuan menumpuk
        if ($alasan = $this->alasanBlokir()) {
            return redirect()->back()->with('error', $alasan);
        }

        $daftarAlat = array_values($request->alat_id);
        $daftarJumlah = array_values($request->jumlah);

        DB::beginTransaction();
        try {
            // Buat header peminjaman dengan status awal 'diajukan'
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tgl_pinjam' => now()->toDateString(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);

            foreach ($daftarAlat as $index => $alatId) {
                $jumlahPinjam = (int) $daftarJumlah[$index];

                $alat = Alat::lockForUpdate()->findOrFail($alatId);

                if (strtolower(trim($alat->status_kondisi)) !== self::KONDISI_BISA_DIPINJAM) {
                    throw new \Exception("Alat '{$alat->nama_alat}' sedang tidak bisa dipinjam.");
                }

                if ($alat->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi (tersedia {$alat->stok}).");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alat->id,
                    'jumlah' => $jumlahPinjam,
                ]);
            }

            DB::commit();
            return redirect()->route('peminjam.riwayat')->with('success', 'Pengajuan peminjaman berhasil dikirim! Menunggu persetujuan Petugas.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }

    // 3. Melihat riwayat peminjaman user yang sedang login
    public function riwayatPeminjaman(PeminjamanService $service)
    {
        $peminjamans = Peminjaman::with(['detailPinjam.alat', 'pengembalian'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        // Hari telat dihitung dengan logika yang sama seperti saat denda disimpan
        $hariTelat = $peminjamans->getCollection()->mapWithKeys(
            fn ($p) => [$p->id => $service->hitungHariTelat($p->tgl_kembali_plan)]
        );

        $totalDenda = Pengembalian::whereHas('peminjaman', fn ($q) => $q->where('user_id', auth()->id()))
            ->sum('denda');

        $ringkasan = [
            'berjalan' => Peminjaman::where('user_id', auth()->id())
                ->whereIn('status', ['dipinjam', 'telat', 'menunggu_pengembalian'])->count(),
            'telat'    => $this->jumlahTelat(),
            'denda'    => (int) $totalDenda,
        ];

        return view('peminjam.riwayat', compact('peminjamans', 'hariTelat', 'ringkasan'));
    }

    // 4. Peminjam mengkonfirmasi bahwa barang ingin dikembalikan (Notifikasi ke Petugas)
    public function prosesPengembalian(Request $request, $id)
    {
        $peminjaman = Peminjaman::where('id', $id)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['dipinjam', 'telat']) // Yang bisa dikembalikan hanya yang berstatus ini
            ->firstOrFail();

        try {
            // HANYA ubah status menjadi menunggu konfirmasi petugas
            $peminjaman->update([
                'status' => 'menunggu_pengembalian'
            ]);

            return redirect()->back()->with('success', 'Pengajuan pengembalian terkirim! Silakan serahkan alat fisik ke loket Petugas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
        }
    }

    // 5. Membatalkan pengajuan peminjaman (Hanya jika statusnya masih 'diajukan')
    public function batalkanPeminjaman($id)
    {
        // Pastikan data milik user tersebut dan statusnya benar-benar MASIH 'diajukan'
        $peminjaman = Peminjaman::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'diajukan')
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Hapus detail peminjaman terlebih dahulu
            $peminjaman->detailPinjam()->delete();

            // Hapus data utama peminjaman
            $peminjaman->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Pengajuan peminjaman berhasil dibatalkan dan dihapus.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal membatalkan peminjaman: ' . $e->getMessage());
        }
    }
}