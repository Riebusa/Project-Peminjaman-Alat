<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;

class PetugasController extends Controller
{
    // Dashboard Petugas
    public function index()
    {
        // Petugas butuh melihat ada berapa pengajuan yang harus diurus
        $peminjamanDiajukan = Peminjaman::where('status', 'diajukan')->count();
        $peminjamanAktif = Peminjaman::where('status', 'dipinjam')->count();

        return view('petugas.dashboard', compact('peminjamanDiajukan', 'peminjamanAktif'));
    }

    public function indexPeminjaman()
    {
        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])->latest()->get();
        return view('petugas.peminjaman.index', compact('peminjamans'));
    }

    // Setujui pengajuan: cek status + stok, lalu kurangi stok (di service)
    public function setujuiPeminjaman($id, PeminjamanService $service)
    {
        try {
            $service->setujui((int) $id);
            return redirect()->back()->with('success', 'Peminjaman disetujui dan stok alat dikurangi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    // Proses pengembalian: denda telat dihitung otomatis, denda kerusakan dari input
    public function prosesPengembalian(Request $request, $peminjamanId, PeminjamanService $service)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => 'nullable|integer|min:0',
        ]);

        try {
            $hasil = $service->terimaPengembalian(
                (int) $peminjamanId,
                $request->kondisi_kembali,
                $request->denda_kerusakan,
                auth()->id()
            );

            return redirect()->back()->with('success', "Pengembalian dicatat dan stok dipulihkan. Telat: {$hasil['hari_telat']} hari. Total denda: Rp " . number_format($hasil['denda_total'], 0, ',', '.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
        }
    }

    // Daftar alat yang sedang dipinjam / diminta dikembalikan oleh peminjam
    public function indexPengembalian()
    {
        $peminjamanAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dipinjam', 'telat', 'menunggu_pengembalian'])
            ->latest()
            ->get();

        return view('petugas.pengembalian.index', compact('peminjamanAktif'));
    }

    // Halaman filter/cetak laporan
    public function laporan()
    {
        return view('petugas.laporan.index');
    }
}