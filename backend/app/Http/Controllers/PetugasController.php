<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    // 1. TAMBAHAN BARU: Fungsi untuk halaman utama/Dashboard Petugas
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
        // PERBAIKAN: ubah string compact menjadi 'peminjamans'
        return view('petugas.peminjaman.index', compact('peminjamans'));
    }

    // PERBAIKAN: Ubah nama fungsi agar sesuai dengan web.php
    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);
            $peminjaman->update(['status' => 'dipinjam']);

            foreach ($peminjaman->detailPinjam as $detail) {
                // PERBAIKAN: Tambahkan lockForUpdate agar aman
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                $alat->decrement('stok', $detail->jumlah);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Peminjaman disetujui dan stok alat dikurangi.');
        } catch (\Exception $e) {
            DB::rollBack();
            // PERBAIKAN: typo gestMessage menjadi getMessage
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function prosesPengembalian(Request $request, $peminjamanId)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string',
            'denda' => 'nullable|integer'
        ]);

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($peminjamanId);

            Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tgl_kembali' => now(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda' => $request->denda ?? 0,
                'petugas_id' => auth()->id()
            ]);

            // PERBAIKAN BUG ENUM: Ubah 'selesai' menjadi 'dikembalikan' sesuai database!
            $peminjaman->update(['status' => 'dikembalikan']);

            foreach ($peminjaman->detailPinjam as $detail) {
                // PERBAIKAN: Tambahkan lockForUpdate
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                $alat->increment('stok', $detail->jumlah);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Pengembalian berhasil dicatat dan stok dipulihkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Memanggil halaman daftar alat yang sedang dipinjam (untuk dikembalikan)
    public function indexPengembalian()
    {
        // Hanya tampilkan yang statusnya 'dipinjam' atau 'telat'
        $peminjamanAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
                            ->whereIn('status', ['dipinjam', 'telat'])
                            ->latest()
                            ->get();
                            
        return view('petugas.pengembalian.index', compact('peminjamanAktif'));
    }

    // Memanggil halaman filter/cetak laporan
    public function laporan()
    {
        return view('petugas.laporan.index');
    }
}