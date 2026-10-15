<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
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

    // Memantau pengembalian: tab "Sedang Dipinjam" (dengan filter) dan tab "Riwayat"
    public function indexPengembalian(Request $request, PeminjamanService $service)
    {
        $search = trim((string) $request->input('search'));

        // Tab aktif (supaya tidak kembali ke tab pertama saat cari / pindah halaman)
        $tab = $request->input('tab', $request->has('riwayat_page') ? 'riwayat' : 'aktif');
        if (!in_array($tab, ['aktif', 'riwayat'], true)) {
            $tab = 'aktif';
        }

        $filter = $request->input('filter', 'semua');
        if (!in_array($filter, ['semua', 'menunggu', 'telat', 'dipinjam'], true)) {
            $filter = 'semua';
        }

        $statusAktif = PeminjamanService::STATUS_BISA_DIKEMBALIKAN;

        // ---- Kartu ringkasan (tidak terpengaruh pencarian / filter) ----
        $ringkasan = [
            'aktif'    => Peminjaman::whereIn('status', $statusAktif)->count(),
            'menunggu' => Peminjaman::where('status', 'menunggu_pengembalian')->count(),
            'telat'    => Peminjaman::whereIn('status', $statusAktif)
                ->whereDate('tgl_kembali_plan', '<', today())->count(),
            'selesai'  => Peminjaman::where('status', 'dikembalikan')->count(),
        ];

        // ---- Tab 1: peminjaman yang alatnya masih di tangan peminjam ----
        $queryAktif = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->whereIn('status', $statusAktif)
            ->when($filter === 'menunggu', fn ($q) => $q->where('status', 'menunggu_pengembalian'))
            ->when($filter === 'telat', fn ($q) => $q->whereDate('tgl_kembali_plan', '<', today()))
            ->when($filter === 'dipinjam', fn ($q) => $q
                ->whereIn('status', ['dipinjam', 'telat'])
                ->whereDate('tgl_kembali_plan', '>=', today()))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('detailPinjam.alat', fn ($a) => $a->where('nama_alat', 'like', "%{$search}%"));
                });
            })
            // Request peminjam di atas, lalu yang paling dekat / paling lewat tenggat
            ->orderByRaw("CASE WHEN status = 'menunggu_pengembalian' THEN 0 ELSE 1 END")
            ->orderBy('tgl_kembali_plan')
            ->orderBy('id');

        $peminjamanAktif = $queryAktif
            ->paginate(10, ['*'], 'aktif_page')
            ->withQueryString()
            ->appends('tab', 'aktif');

        // Hari telat dihitung dengan logika yang sama seperti saat denda disimpan
        $hariTelat = $peminjamanAktif->getCollection()->mapWithKeys(
            fn ($p) => [$p->id => $service->hitungHariTelat($p->tgl_kembali_plan)]
        );

        // ---- Tab 2: riwayat pengembalian yang sudah diproses ----
        $queryRiwayat = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->whereHas('peminjaman.user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('peminjaman.detailPinjam.alat', fn ($a) => $a->where('nama_alat', 'like', "%{$search}%"))
                      ->orWhere('kondisi_kembali', 'like', "%{$search}%");
                });
            });

        $totalDendaRiwayat = (int) (clone $queryRiwayat)->sum('denda');

        $riwayat = $queryRiwayat->latest('tgl_kembali')->latest('id')
            ->paginate(10, ['*'], 'riwayat_page')
            ->withQueryString()
            ->appends('tab', 'riwayat');

        return view('petugas.pengembalian.index', compact(
            'peminjamanAktif', 'hariTelat', 'riwayat', 'ringkasan',
            'totalDendaRiwayat', 'search', 'tab', 'filter'
        ));
    }

    // Proses pengembalian: denda telat dihitung otomatis, denda kerusakan dari input
    public function prosesPengembalian(Request $request, $peminjamanId, PeminjamanService $service)
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