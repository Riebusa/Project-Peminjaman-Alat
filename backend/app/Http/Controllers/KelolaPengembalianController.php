<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
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

    // Proses Terima Pengembalian (Dari Request Peminjam)
    public function prosesTerima(Request $request, $id)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => 'nullable|numeric|min:0',
        ]);

        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        // HITUNG DENDA KETERLAMBATAN OTOMATIS (Aman dari nilai minus)
        $tgl_plan = Carbon::parse($peminjaman->tgl_kembali_plan);
        $hari_ini = Carbon::today();
        
        $keterlambatan_hari = 0;
        if ($hari_ini->greaterThan($tgl_plan)) {
            $keterlambatan_hari = $tgl_plan->diffInDays($hari_ini);
        }
        
        $denda_telat = $keterlambatan_hari * 1000;
        $denda_total = $denda_telat + ($request->denda_kerusakan ?? 0);

        DB::beginTransaction();
        try {
            // 1. Simpan ke tabel Pengembalian
            Pengembalian::create([
                'peminjaman_id'   => $peminjaman->id,
                'tgl_kembali'     => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $denda_total,
                'petugas_id'      => auth()->id(),
            ]);

            // 2. Ubah status menjadi dikembalikan
            $peminjaman->update(['status' => 'dikembalikan']);

            // 3. Kembalikan stok
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->find($detail->alat_id);
                if ($alat) {
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            DB::commit();
            return back()->with('success', "Pengembalian berhasil. Keterlambatan: {$keterlambatan_hari} hari. Total Denda: Rp " . number_format($denda_total, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollback();
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
    public function storeManual(Request $request)
    {
        $request->validate([
            'peminjaman_id'   => 'required|exists:peminjaman,id',
            'kondisi_kembali' => 'required|string|max:255',
            'denda_kerusakan' => 'nullable|numeric|min:0',
        ]);

        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($request->peminjaman_id);

        // Hitung denda keterlambatan otomatis (Aman dari nilai minus)
        $tgl_plan = Carbon::parse($peminjaman->tgl_kembali_plan);
        $hari_ini = Carbon::today();
        
        $keterlambatan_hari = 0;
        if ($hari_ini->greaterThan($tgl_plan)) {
            $keterlambatan_hari = $tgl_plan->diffInDays($hari_ini);
        }
        
        $denda_telat = $keterlambatan_hari * 1000;
        $denda_total = $denda_telat + ($request->denda_kerusakan ?? 0);

        DB::beginTransaction();
        try {
            Pengembalian::create([
                'peminjaman_id'   => $peminjaman->id,
                'tgl_kembali'     => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $denda_total,
                'petugas_id'      => auth()->id(),
            ]);

            $peminjaman->update(['status' => 'dikembalikan']);

            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->find($detail->alat_id);
                if ($alat) {
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            DB::commit();
            return redirect()->route('admin.pengembalian.index')
                ->with('success', "Pengembalian manual berhasil. Denda Keterlambatan: Rp " . number_format($denda_telat, 0, ',', '.') . " | Total Denda: Rp " . number_format($denda_total, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Gagal menyimpan pengembalian: ' . $e->getMessage());
        }
    }
}