<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelolaPengembalianController extends Controller
{
    public function index()
    {
        // 1. Belum Diproses (Request dari Peminjam yang statusnya 'menunggu_pengembalian')
        $belumDiproses = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->where('status', 'menunggu_pengembalian')
            ->latest()
            ->get();

        // 2. Sudah Diproses (Riwayat Pengembalian lengkap dengan relasi alat)
        $sudahDiproses = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas'])
            ->latest()
            ->get();

        // 3. Data Peminjaman Aktif (Untuk form manual)
        $peminjamanAktif = Peminjaman::with(['user'])
            ->whereIn('status', ['dipinjam', 'telat'])
            ->get();

        return view('admin.pengembalian.index', compact('belumDiproses', 'sudahDiproses', 'peminjamanAktif'));
    }

    public function prosesTerima(Request $request, $id, PeminjamanService $service)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => 'nullable|numeric|min:0',
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


    // Tolak Request Pengembalian
    public function tolakPengembalian($id)
    {
        $peminjaman = Peminjaman::findOrFail($id);
        
        if ($peminjaman->status === 'menunggu_pengembalian') {
            $tgl_plan = Carbon::parse($peminjaman->tgl_kembali_plan);
            $status_baru = Carbon::today()->greaterThan($tgl_plan) ? 'telat' : 'dipinjam';

            $peminjaman->update(['status' => $status_baru]);
            
            return back()->with('success', 'Request pengembalian ditolak. Status dikembalikan ke ' . $status_baru);
        }
        
        return back()->with('error', 'Data tidak valid.');
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
            'denda_kerusakan' => 'nullable|numeric|min:0',
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
            return back()->with('error', 'Gagal menyimpan pengembalian: ' . $e->getMessage());
        }
    }
}