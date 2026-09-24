<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelolaPeminjamanController extends Controller
{
    public function index()
    {
        // 1. Data Belum Selesai (Diajukan, Dipinjam, Telat, Menunggu Pengembalian)
        $peminjamanAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['diajukan', 'dipinjam', 'telat', 'menunggu_pengembalian'])
            ->latest()
            ->get();

        // 2. Data Sudah Selesai (Dikembalikan, Ditolak)
        $peminjamanSelesai = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dikembalikan', 'ditolak'])
            ->latest()
            ->get();

        return view('admin.peminjaman.index', compact('peminjamanAktif', 'peminjamanSelesai'));
    }

    // Konfirmasi Peminjaman (Dari Diajukan -> Dipinjam)
    public function setujui($id)
    {
        $peminjaman = Peminjaman::findOrFail($id);
        if ($peminjaman->status === 'diajukan') {
            $peminjaman->update(['status' => 'dipinjam']);
            return back()->with('success', 'Peminjaman disetujui. Alat siap diserahkan.');
        }
        return back()->with('error', 'Status tidak valid.');
    }

    // Tolak Peminjaman (Dari Diajukan -> Ditolak)
    public function tolak($id)
    {
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            $peminjaman->update(['status' => 'ditolak']);
            
            // Kembalikan stok karena batal dipinjam
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->find($detail->alat_id);
                if ($alat) {
                    $alat->increment('stok', $detail->jumlah);
                }
            }
            DB::commit();
            return back()->with('success', 'Peminjaman ditolak dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }
    
    // Menampilkan halaman form tambah peminjaman (create.blade.php)
    public function create()
    {
        $users = \App\Models\User::where('role', 'peminjam')->get();
        $alats = \App\Models\Alat::where('stok', '>', 0)->get();
        
        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // Menyimpan data peminjaman dari form manual
    public function store(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'tgl_pinjam'       => 'required|date',
            'tgl_kembali_plan' => 'required|date|after_or_equal:tgl_pinjam',
            'alat_id'          => 'required|array',
            'alat_id.*'        => 'exists:alat,id',
            'jumlah'           => 'required|array',
            'jumlah.*'         => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // 1. Buat transaksi utama dengan status 'dipinjam' (karena admin yang input)
            $peminjaman = Peminjaman::create([
                'user_id'          => $request->user_id,
                'tgl_pinjam'       => $request->tgl_pinjam,
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status'           => 'dipinjam', 
            ]);

            // 2. Simpan detail dan kurangi stok alat
            foreach ($request->alat_id as $key => $alat_id) {
                $jumlah = $request->jumlah[$key];
                
                // Kurangi stok alat
                $alat = Alat::lockForUpdate()->find($alat_id);
                if ($alat->stok < $jumlah) {
                    throw new \Exception("Stok {$alat->nama_alat} tidak mencukupi.");
                }
                $alat->decrement('stok', $jumlah);

                // Simpan ke tabel detail
                \App\Models\DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id'       => $alat_id,
                    'jumlah'        => $jumlah,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Transaksi peminjaman berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        }
    }

    // Menghapus data peminjaman permanen
    public function destroy($id)
    {
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);
        
        DB::beginTransaction();
        try {
            // KEAMANAN STOK: Jika statusnya sudah dipinjam/telat/menunggu, kembalikan stok alatnya dulu!
            if (in_array($peminjaman->status, ['dipinjam', 'telat', 'menunggu_pengembalian'])) {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = \App\Models\Alat::lockForUpdate()->find($detail->alat_id);
                    if ($alat) {
                        $alat->increment('stok', $detail->jumlah);
                    }
                }
            }

            // Hapus detail peminjaman
            $peminjaman->detailPinjam()->delete();
            
            // Hapus data utama
            $peminjaman->delete();
            
            DB::commit();
            return back()->with('success', 'Data transaksi berhasil dihapus permanen dan stok alat telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}