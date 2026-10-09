<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Services\PeminjamanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PetugasController extends Controller
{
    // Dipakai untuk tab filter persetujuan dan filter laporan
    private const STATUS_FILTER = [
        'semua', 'diajukan', 'dipinjam', 'telat',
        'menunggu_pengembalian', 'dikembalikan', 'ditolak',
    ];

    // Dashboard Petugas
    public function index()
    {
        return view('petugas.dashboard', [
            'peminjamanDiajukan'   => Peminjaman::where('status', 'diajukan')->count(),
            'peminjamanAktif'      => Peminjaman::whereIn('status', ['dipinjam', 'telat'])->count(),
            'menungguPengembalian' => Peminjaman::where('status', 'menunggu_pengembalian')->count(),
            'peminjamanTelat'      => Peminjaman::whereIn('status', ['dipinjam', 'telat'])
                ->whereDate('tgl_kembali_plan', '<', today())->count(),
        ]);
    }

    // Halaman persetujuan: tab filter status, default "diajukan"
    public function indexPeminjaman(Request $request)
    {
        $status = $request->input('status', 'diajukan');
        if (!in_array($status, self::STATUS_FILTER, true)) {
            $status = 'diajukan';
        }

        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            // Pengajuan: yang paling lama menunggu tampil paling atas
            ->orderBy('id', $status === 'diajukan' ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString();

        $jumlahDiajukan = Peminjaman::where('status', 'diajukan')->count();

        return view('petugas.peminjaman.index', compact('peminjamans', 'status', 'jumlahDiajukan'));
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

    // Tolak pengajuan (stok tidak berubah)
    public function tolakPeminjaman($id, PeminjamanService $service)
    {
        try {
            $service->tolak((int) $id);
            return redirect()->back()->with('success', 'Pengajuan peminjaman ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }

    // Daftar alat yang sedang dipinjam / diminta dikembalikan oleh peminjam
    public function indexPengembalian(PeminjamanService $service)
    {
        $peminjamanAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', ['dipinjam', 'telat', 'menunggu_pengembalian'])
            ->latest()
            ->get();

        // Hari telat dihitung dengan logika yang sama seperti saat denda disimpan
        $hariTelat = $peminjamanAktif->mapWithKeys(
            fn ($p) => [$p->id => $service->hitungHariTelat($p->tgl_kembali_plan)]
        );

        return view('petugas.pengembalian.index', compact('peminjamanAktif', 'hariTelat'));
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

    // Tolak request pengembalian dari peminjam
    public function tolakPengembalian($id, PeminjamanService $service)
    {
        try {
            $peminjaman = $service->tolakPengembalian((int) $id);
            return redirect()->back()->with('success', "Request pengembalian ditolak. Status dikembalikan ke {$peminjaman->status}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menolak request: ' . $e->getMessage());
        }
    }

    // ==========================================
    // LAPORAN
    // ==========================================

    // Halaman filter + tabel rekap
    public function laporan(Request $request)
    {
        $peminjamans = null;
        $ringkasan = null;

        // Data baru ditampilkan setelah form filter dikirim
        if ($request->filled('start_date')) {
            $filter = $this->validasiFilterLaporan($request);
            $peminjamans = $this->dataLaporan($filter);
            $ringkasan = $this->ringkasanLaporan($peminjamans);
        }

        return view('petugas.laporan.index', compact('peminjamans', 'ringkasan'));
    }

    // Cetak laporan ke PDF (dibuka di tab baru)
    public function cetakLaporan(Request $request)
    {
        $filter = $this->validasiFilterLaporan($request);
        $peminjamans = $this->dataLaporan($filter);
        $ringkasan = $this->ringkasanLaporan($peminjamans);

        $pdf = Pdf::loadView('petugas.laporan.pdf', [
            'filter'      => $filter,
            'peminjamans' => $peminjamans,
            'ringkasan'   => $ringkasan,
            'petugas'     => auth()->user()->name,
            'dicetak'     => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("laporan-peminjaman-{$filter['start_date']}-sd-{$filter['end_date']}.pdf");
    }

    private function validasiFilterLaporan(Request $request): array
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'nullable|in:' . implode(',', self::STATUS_FILTER),
        ]);

        $data['status'] = $data['status'] ?? 'semua';

        return $data;
    }

    private function dataLaporan(array $filter): Collection
    {
        return Peminjaman::with(['user', 'detailPinjam.alat', 'pengembalian'])
            ->whereDate('tgl_pinjam', '>=', $filter['start_date'])
            ->whereDate('tgl_pinjam', '<=', $filter['end_date'])
            ->when($filter['status'] !== 'semua', fn ($q) => $q->where('status', $filter['status']))
            ->orderBy('tgl_pinjam')
            ->orderBy('id')
            ->get();
    }

    private function ringkasanLaporan(Collection $peminjamans): array
    {
        return [
            'total'       => $peminjamans->count(),
            'total_denda' => (int) $peminjamans->sum(fn ($p) => $p->pengembalian->denda ?? 0),
            'per_status'  => $peminjamans->groupBy('status')->map->count(),
        ];
    }
}