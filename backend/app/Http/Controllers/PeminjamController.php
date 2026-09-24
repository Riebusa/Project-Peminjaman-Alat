<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // 1. Melihat daftar/katalog alat yang tersedia
    public function katalogAlat(Request $request)
    {
        $search = $request->input('search');
        
        // Menampilkan alat lengkap dengan pencarian sederhana
        $alats = Alat::with('kategori')
            ->where('stok', '>', 0)
            ->when($search, function ($query, $search) {
                return $query->where('nama_alat', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('peminjam.katalog', compact('alats', 'search'));
    }

    // 2. Mengajukan peminjaman baru
    public function ajukanPeminjaman(Request $request)
    {
        $request->validate([
            'tgl_kembali_plan' => 'required|date|after_or_equal:today', // after:today membuat tidak bisa pinjam 1 hari
            'alat_id' => 'required|array',
            'jumlah' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Buat header peminjaman dengan status awal 'diajukan'
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tgl_pinjam' => now()->toDateString(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan', 
            ]);

            // Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $index => $alatId) {
                $jumlahPinjam = $request->jumlah[$index];
                
                // KUNCI STOK: Mencegah bentrok jika ada 2 user meminjam barang yang sama di detik yang sama
                $alat = Alat::lockForUpdate()->findOrFail($alatId);

                if ($alat->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
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
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(10); // Diubah jadi paginate agar rapi jika data banyak

        return view('peminjam.riwayat', compact('peminjamans'));
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
            // Hapus detail peminjaman terlebih dahulu (jika database Anda tidak menggunakan onDelete cascade)
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