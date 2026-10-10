<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\DetailPinjam;
use App\Models\Peminjaman;
use App\Models\User;
use App\Services\PeminjamanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KelolaPeminjamanController extends Controller
{
    // Admin boleh mencatat peminjaman yang terjadi paling lama sekian hari ke belakang
    private const MAKS_MUNDUR_HARI = 7;
    private const KONDISI_BISA_DIPINJAM = 'baik';

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

    // Konfirmasi Peminjaman (Diajukan -> Dipinjam). Stok dikurangi di service.
    public function setujui($id, PeminjamanService $service)
    {
        try {
            $service->setujui((int) $id);
            return back()->with('success', 'Peminjaman disetujui, stok alat dikurangi. Alat siap diserahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    // Tolak Peminjaman (Diajukan -> Ditolak). Stok TIDAK diubah.
    public function tolak($id, PeminjamanService $service)
    {
        try {
            $service->tolak((int) $id);
            return back()->with('success', 'Peminjaman ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }

    // Menampilkan halaman form tambah peminjaman (create.blade.php)
    public function create()
    {
        // Hanya peminjam yang aktif, dan alat berkondisi baik yang stoknya masih ada
        $users = User::where('role', 'peminjam')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $alats = Alat::where('stok', '>', 0)
            ->whereRaw('LOWER(TRIM(status_kondisi)) = ?', [self::KONDISI_BISA_DIPINJAM])
            ->orderBy('nama_alat')
            ->get();

        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // Menyimpan data peminjaman dari form manual
    public function store(Request $request)
    {
        $jumlahAlat = is_array($request->alat_id) ? count($request->alat_id) : 0;

        $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($q) => $q->where('role', 'peminjam')->where('is_active', true)
                ),
            ],
            'tgl_pinjam' => [
                'required', 'date',
                'after_or_equal:' . today()->subDays(self::MAKS_MUNDUR_HARI)->toDateString(),
                'before_or_equal:today',
            ],
            'tgl_kembali_plan' => ['required', 'date', 'after_or_equal:tgl_pinjam'],
            'alat_id'   => ['required', 'array', 'min:1'],
            'alat_id.*' => ['required', 'integer', 'distinct', 'exists:alat,id'],
            'jumlah'    => ['required', 'array', 'size:' . $jumlahAlat],
            'jumlah.*'  => ['required', 'integer', 'min:1'],
        ], [
            'user_id.exists'                   => 'Peminjam tidak ditemukan atau akunnya nonaktif.',
            'tgl_pinjam.after_or_equal'        => 'Tanggal pinjam paling lama ' . self::MAKS_MUNDUR_HARI . ' hari ke belakang.',
            'tgl_pinjam.before_or_equal'       => 'Tanggal pinjam tidak boleh di masa depan.',
            'tgl_kembali_plan.after_or_equal'  => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
            'alat_id.*.exists'                 => 'Alat yang dipilih tidak ditemukan.',
            'alat_id.*.distinct'               => 'Alat yang sama tidak boleh dipilih dua kali.',
            'jumlah.size'                      => 'Setiap alat harus memiliki jumlah.',
            'jumlah.*.integer'                 => 'Jumlah pinjam harus berupa angka bulat.',
            'jumlah.*.min'                     => 'Jumlah pinjam minimal 1.',
        ]);

        $daftarAlat = array_values($request->alat_id);
        $daftarJumlah = array_values($request->jumlah);

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
            foreach ($daftarAlat as $index => $alatId) {
                $jumlah = (int) $daftarJumlah[$index];

                $alat = Alat::lockForUpdate()->findOrFail($alatId);

                if (strtolower(trim($alat->status_kondisi)) !== self::KONDISI_BISA_DIPINJAM) {
                    throw new \Exception("Alat '{$alat->nama_alat}' sedang tidak bisa dipinjam.");
                }

                if ($alat->stok < $jumlah) {
                    throw new \Exception("Stok {$alat->nama_alat} tidak mencukupi (tersedia {$alat->stok}).");
                }

                $alat->decrement('stok', $jumlah);

                // Simpan ke tabel detail
                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id'       => $alat->id,
                    'jumlah'        => $jumlah,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Transaksi peminjaman berhasil ditambahkan!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
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
                    $alat = Alat::lockForUpdate()->find($detail->alat_id);
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