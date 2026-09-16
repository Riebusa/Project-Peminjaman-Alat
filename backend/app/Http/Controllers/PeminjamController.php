<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamController extends Controller
{
    // Melihat daftar/katalog alat yang tersedia
    public function katalogAlat()
    {
        $alats = Alat::with('kategori')->where('stok', '>', 0)->get();
        return view('peminjam.katalog', compact('alats'));
    }

    public function ajukanPeminjaman(Request $request)
    {
        $request->validate([
            'tgl_kembali_plan' => 'required|date|after:today',
            'alat_id' => 'required|array',
            'jumlah' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Buat header peminjaman
            $peminjaman = Peminjaman::create([
                'user_id' => auth()->id(),
                'tgl_pinjam' => now(),
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan',
            ]);

            // Masukkan daftar alat yang dipinjam ke detail_pinjam
            foreach ($request->alat_id as $index => $alatId) {
                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $request->jumlah[$index],
                ]);
            }

            DB::commit();
            return redirect()->route('peminjam.riwayat')->with('success', 'Pengajuan peminjaman berhasil dikirim.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal mengajukan peminjaman: ' . $e->getMessage());
        }
    }

    // Melihat riwayat peminjaman user yang sedang login
    public function riwayatPeminjaman()
    {
        $peminjamans = Peminjaman::with('detailPinjam.alat')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('peminjam.riwayat', compact('peminjamans'));
    }

    public function prosesPengembalian(Request $request, $id)
    {
        // 1. Validasi input dari form pengembalian
        $request->validate([
            'kondisi_kembali' => 'required|string',
            'denda'           => 'nullable|numeric|min:0',
        ]);

        // 2. Ambil data peminjaman beserta detail alatnya
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        // Pastikan status peminjaman memang sedang dipinjam
        // Sesuaikan string 'dipinjam' dengan value status di database Anda
        if ($peminjaman->status !== 'dipinjam') {
            return redirect()->back()->with('error', 'Alat tidak sedang dipinjam atau sudah dikembalikan.');
        }

        DB::beginTransaction();
        try {
            // 3. Masukkan data ke tabel pengembalian
            \App\Models\Pengembalian::create([
                'peminjaman_id'   => $peminjaman->id,
                'tgl_kembali'     => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $request->denda ?? 0,
                // Asumsi: yang memproses pengembalian adalah petugas yang sedang login
                'petugas_id'      => auth()->id(), 
            ]);

            // 4. Ubah status peminjaman menjadi selesai/dikembalikan
            $peminjaman->update([
                'status' => 'dikembalikan' 
            ]);

            // 5. Kembalikan stok alat dengan melakukan looping pada detail pinjam
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = \App\Models\Alat::find($detail->alat_id);
                if ($alat) {
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Pengembalian berhasil diproses, stok telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
        }
    }
}