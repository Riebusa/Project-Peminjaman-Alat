<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class KelolaPengembalianController extends Controller
{
    private const PER_HALAMAN = 10;

    public function index(Request $request, PeminjamanService $service)
    {
        $search = trim((string) $request->input('search'));

        // Tab yang sedang dibuka (supaya tidak kembali ke tab pertama saat pindah halaman)
        $tabAwal = $request->input('tab', $request->has('riwayat_page') ? 'riwayat' : 'request');
        if (!in_array($tabAwal, ['request', 'riwayat'], true)) {
            $tabAwal = 'request';
        }

        // 1. Belum Diproses (Request dari Peminjam yang statusnya 'menunggu_pengembalian')
        $queryRequest = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->where('status', 'menunggu_pengembalian');

        if ($search !== '') {
            $queryRequest->where(function ($w) use ($search) {
                $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('detailPinjam.alat', fn ($a) => $a->where('nama_alat', 'like', "%{$search}%"));
            });
        }

        $belumDiproses = $queryRequest->latest()
            ->paginate(self::PER_HALAMAN, ['*'], 'request_page')
            ->withQueryString()
            ->appends('tab', 'request');

        // Hari telat dihitung dengan logika yang sama seperti saat denda disimpan
        $hariTelat = $belumDiproses->getCollection()->mapWithKeys(
            fn ($p) => [$p->id => $service->hitungHariTelat($p->tgl_kembali_plan)]
        );

        // 2. Sudah Diproses (Riwayat Pengembalian lengkap dengan relasi alat)
        $queryRiwayat = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas']);

        if ($search !== '') {
            $queryRiwayat->where(function ($w) use ($search) {
                $w->whereHas('peminjaman.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('peminjaman.detailPinjam.alat', fn ($a) => $a->where('nama_alat', 'like', "%{$search}%"))
                  ->orWhere('kondisi_kembali', 'like', "%{$search}%");
            });
        }

        $sudahDiproses = $queryRiwayat->latest()
            ->paginate(self::PER_HALAMAN, ['*'], 'riwayat_page')
            ->withQueryString()
            ->appends('tab', 'riwayat');

        return view('admin.pengembalian.index', compact('belumDiproses', 'sudahDiproses', 'hariTelat', 'search', 'tabAwal'));
    }

    // Proses Terima Pengembalian (Dari Request Peminjam)
    public function prosesTerima(Request $request, $id, PeminjamanService $service)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => ['nullable', 'integer', 'min:0', 'max:' . PeminjamanService::MAKS_DENDA_KERUSAKAN],
        ], [
            'denda_kerusakan.integer' => 'Denda kerusakan harus berupa angka bulat.',
            'denda_kerusakan.max'     => 'Denda kerusakan maksimal Rp ' . number_format(PeminjamanService::MAKS_DENDA_KERUSAKAN, 0, ',', '.') . '.',
        ]);

        try {
            $hasil = $service->terimaPengembalian(
                (int) $id,
                $request->kondisi_kembali,
                $request->denda_kerusakan,
                auth()->id()
            );

            return back()->with('success', "Pengembalian berhasil. Keterlambatan: {$hasil['hari_telat']} hari. Total Denda: Rp " . number_format($hasil['denda_total'], 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    // Tolak Request Pengembalian (logika di service, sama dengan petugas)
    public function tolakPengembalian($id, PeminjamanService $service)
    {
        try {
            $peminjaman = $service->tolakPengembalian((int) $id);
            return back()->with('success', "Request pengembalian ditolak. Status dikembalikan ke {$peminjaman->status}.");
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Transaksi tidak ditemukan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak request: ' . $e->getMessage());
        }
    }

    // Menampilkan form tambah pengembalian manual
    public function create()
    {
        $peminjamanAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dipinjam', 'telat'])
            ->get();

        $users = $peminjamanAktif->pluck('user')->unique('id');

        return view('admin.pengembalian.create', compact('peminjamanAktif', 'users'));
    }

    // Memproses form tambah pengembalian manual
    public function storeManual(Request $request, PeminjamanService $service)
    {
        $request->validate([
            'peminjaman_id'   => 'required|exists:peminjaman,id',
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => ['nullable', 'integer', 'min:0', 'max:' . PeminjamanService::MAKS_DENDA_KERUSAKAN],
        ], [
            'denda_kerusakan.integer' => 'Denda kerusakan harus berupa angka bulat.',
            'denda_kerusakan.max'     => 'Denda kerusakan maksimal Rp ' . number_format(PeminjamanService::MAKS_DENDA_KERUSAKAN, 0, ',', '.') . '.',
        ]);

        try {
            $hasil = $service->terimaPengembalian(
                (int) $request->peminjaman_id,
                $request->kondisi_kembali,
                $request->denda_kerusakan,
                auth()->id()
            );

            return redirect()->route('admin.pengembalian.index')
                ->with('success', "Pengembalian manual berhasil. Denda Keterlambatan: Rp " . number_format($hasil['denda_telat'], 0, ',', '.') . " | Total Denda: Rp " . number_format($hasil['denda_total'], 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan pengembalian: ' . $e->getMessage());
        }
    }
}