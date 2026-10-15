@extends('layouts.dev')

@section('title', 'Pantau Pengembalian Alat')
@section('header-title', 'Pantau Pengembalian Alat')

@section('content')
@php
    $dendaPerHari = \App\Services\PeminjamanService::DENDA_PER_HARI;
    $maksDenda    = \App\Services\PeminjamanService::MAKS_DENDA_KERUSAKAN;

    $filterList = [
        'semua'    => 'Semua',
        'menunggu' => 'Request Peminjam',
        'telat'    => 'Telat',
        'dipinjam' => 'Masih Aman',
    ];
@endphp

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Pantau Pengembalian</h2>
    <p class="text-slate-500 mt-1">Pantau alat yang sedang dipinjam, proses pengembaliannya, dan lihat riwayat yang sudah selesai.</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg text-sm font-medium">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm font-medium">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm font-medium">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<!-- Kartu Ringkasan -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <a href="{{ route('petugas.pengembalian.index', ['tab' => 'aktif']) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Sedang Dipinjam</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $ringkasan['aktif'] }}</p>
    </a>
    <a href="{{ route('petugas.pengembalian.index', ['tab' => 'aktif', 'filter' => 'menunggu']) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Request Pengembalian</p>
        <p class="text-3xl font-bold text-purple-600 mt-1">{{ $ringkasan['menunggu'] }}</p>
    </a>
    <a href="{{ route('petugas.pengembalian.index', ['tab' => 'aktif', 'filter' => 'telat']) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Lewat Tenggat</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ $ringkasan['telat'] }}</p>
    </a>
    <a href="{{ route('petugas.pengembalian.index', ['tab' => 'riwayat']) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Sudah Dikembalikan</p>
        <p class="text-3xl font-bold text-emerald-600 mt-1">{{ $ringkasan['selesai'] }}</p>
    </a>
</div>

<!-- Pencarian -->
<form action="{{ route('petugas.pengembalian.index') }}" method="GET" class="flex w-full md:w-96 mb-4">
    <input type="hidden" name="tab" id="input-tab" value="{{ $tab }}">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <input type="text" name="search" value="{{ $search }}" placeholder="Cari peminjam, alat, atau kondisi..."
        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">Cari</button>
    @if($search !== '')
        <a href="{{ route('petugas.pengembalian.index', ['tab' => $tab, 'filter' => $filter]) }}"
            class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">Reset</a>
    @endif
</form>

<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">

    <!-- Navigasi Tab -->
    <div class="flex border-b border-gray-200 bg-gray-50">
        <button type="button" onclick="switchTab('aktif')" id="tab-btn-aktif" class="px-6 py-3 text-sm font-bold">
            Sedang Dipinjam ({{ $peminjamanAktif->total() }})
        </button>
        <button type="button" onclick="switchTab('riwayat')" id="tab-btn-riwayat" class="px-6 py-3 text-sm font-bold">
            Riwayat Pengembalian ({{ $riwayat->total() }})
        </button>
    </div>

    <!-- TAB 1: Sedang Dipinjam -->
    <div id="tab-aktif" class="hidden">
        <!-- Filter status -->
        <div class="flex flex-wrap gap-2 px-4 py-3 border-b border-slate-100 bg-white">
            @foreach($filterList as $key => $label)
                <a href="{{ route('petugas.pengembalian.index', array_filter(['tab' => 'aktif', 'filter' => $key, 'search' => $search])) }}"
                   class="text-xs font-bold px-3 py-1.5 rounded-full border transition
                   {{ $filter === $key ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">Transaksi / Waktu</th>
                        <th class="px-6 py-4 font-medium">Data Peminjam</th>
                        <th class="px-6 py-4 font-medium">Alat yang Dibawa</th>
                        <th class="px-6 py-4 font-medium">Status Waktu</th>
                        <th class="px-6 py-4 text-center font-medium w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($peminjamanAktif as $pinjam)
                        @php
                            $hari = $hariTelat[$pinjam->id] ?? 0;
                            $isTerlambat = $hari > 0;
                            $tglPlan = \Carbon\Carbon::parse($pinjam->tgl_kembali_plan);
                            $dendaTelat = $hari * $dendaPerHari;
                            $sisaHari = $isTerlambat ? 0 : (int) \Carbon\Carbon::today()->diffInDays($tglPlan->copy()->startOfDay());

                            // Data untuk modal proses pengembalian
                            $dataModal = [
                                'id'         => $pinjam->id,
                                'peminjam'   => $pinjam->user->name ?? 'User Terhapus',
                                'email'      => $pinjam->user->email ?? '-',
                                'tglPinjam'  => \Carbon\Carbon::parse($pinjam->tgl_pinjam)->translatedFormat('d M Y'),
                                'tglPlan'    => $tglPlan->translatedFormat('d M Y'),
                                'hariTelat'  => $hari,
                                'dendaTelat' => $dendaTelat,
                                'alat'       => $pinjam->detailPinjam->map(fn ($d) => ($d->alat->nama_alat ?? 'Alat Terhapus') . ' (' . $d->jumlah . 'x)')->values(),
                            ];
                        @endphp
                    <tr class="hover:bg-slate-50 transition-colors {{ $isTerlambat ? 'bg-red-50/30' : '' }}">
                        <td class="px-6 py-4 align-top">
                            <span class="font-mono text-sm font-bold text-slate-700 block">#TRX-{{ $pinjam->id }}</span>
                            <span class="text-xs text-slate-500 block">Mulai: {{ \Carbon\Carbon::parse($pinjam->tgl_pinjam)->translatedFormat('d M Y') }}</span>
                            <x-status-badge :status="$pinjam->status" class="mt-1.5" />
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm font-bold text-slate-800">{{ $pinjam->user->name ?? 'User Terhapus' }}</p>
                            <p class="text-xs text-slate-500">{{ $pinjam->user->email ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                                @foreach($pinjam->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} <span class="font-bold text-slate-500">({{ $detail->jumlah }}x)</span></li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-xs text-slate-500 mb-1.5">Tenggat: <span class="font-semibold text-slate-700">{{ $tglPlan->translatedFormat('d M Y') }}</span></p>
                            @if($isTerlambat)
                                <div class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 px-2.5 py-1 rounded-md border border-red-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-xs font-bold uppercase tracking-wider">Telat {{ $hari }} Hari</span>
                                </div>
                                <p class="text-[11px] text-red-600 mt-1 font-semibold">Denda telat: Rp {{ number_format($dendaTelat, 0, ',', '.') }}</p>
                            @else
                                <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md border border-blue-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-xs font-bold uppercase tracking-wider">Aman</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">
                                    {{ $sisaHari === 0 ? 'Jatuh tempo hari ini' : 'Sisa waktu: ' . $sisaHari . ' Hari' }}
                                </p>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top text-center">
                            <div class="flex flex-col gap-2">
                                <button type="button" data-info="{{ json_encode($dataModal) }}" onclick="bukaModalPengembalian(JSON.parse(this.dataset.info))"
                                    class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition w-full">
                                    Proses
                                </button>

                                @if($pinjam->status === 'menunggu_pengembalian')
                                    <form action="{{ route('petugas.pengembalian.tolak', $pinjam->id) }}" method="POST" onsubmit="return confirm('Tolak request pengembalian ini? Status akan dikembalikan seperti semula.');">
                                        @csrf
                                        <button type="submit" class="bg-white hover:bg-red-50 text-red-600 border border-red-200 text-xs font-bold px-4 py-2 rounded-lg transition w-full">
                                            Tolak Request
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            @if($search !== '' || $filter !== 'semua')
                                <p class="text-base font-semibold text-slate-700">Tidak Ada Data yang Cocok</p>
                                <p class="text-sm text-slate-500 mt-1">Coba ubah kata kunci pencarian atau filter status.</p>
                            @else
                                <p class="text-base font-semibold text-slate-700">Semua Alat Telah Kembali</p>
                                <p class="text-sm text-slate-500 mt-1">Saat ini tidak ada alat yang sedang dipinjam oleh user.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($peminjamanAktif->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $peminjamanAktif->links() }}
            </div>
        @endif
    </div>

    <!-- TAB 2: Riwayat Pengembalian -->
    <div id="tab-riwayat" class="hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-white text-sm text-slate-600">
            Total denda{{ $search !== '' ? ' (sesuai pencarian)' : '' }}:
            <span class="font-bold {{ $totalDendaRiwayat > 0 ? 'text-red-600' : 'text-emerald-600' }}">Rp {{ number_format($totalDendaRiwayat, 0, ',', '.') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">Transaksi / Tanggal</th>
                        <th class="px-6 py-4 font-medium">Peminjam</th>
                        <th class="px-6 py-4 font-medium">Alat yang Dikembalikan</th>
                        <th class="px-6 py-4 font-medium">Kondisi Alat</th>
                        <th class="px-6 py-4 font-medium">Total Denda</th>
                        <th class="px-6 py-4 font-medium">Diproses Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayat as $pengembalian)
                        @php
                            $pinjamRiwayat = $pengembalian->peminjaman;
                            $telatRiwayat = 0;
                            if ($pinjamRiwayat) {
                                $rencana = \Carbon\Carbon::parse($pinjamRiwayat->tgl_kembali_plan)->startOfDay();
                                $kembali = \Carbon\Carbon::parse($pengembalian->tgl_kembali)->startOfDay();
                                $telatRiwayat = $kembali->greaterThan($rencana) ? (int) $rencana->diffInDays($kembali) : 0;
                            }
                        @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 align-top">
                            <span class="font-mono text-sm font-bold text-slate-700 block">#TRX-{{ $pengembalian->peminjaman_id }}</span>
                            @if($pinjamRiwayat)
                                <span class="text-xs text-slate-500 block">Pinjam: {{ \Carbon\Carbon::parse($pinjamRiwayat->tgl_pinjam)->translatedFormat('d M Y') }}</span>
                            @endif
                            <span class="text-xs text-slate-500 block">Kembali: {{ \Carbon\Carbon::parse($pengembalian->tgl_kembali)->translatedFormat('d M Y') }}</span>
                            @if($telatRiwayat > 0)
                                <span class="inline-block mt-1 text-[11px] font-bold text-red-700 bg-red-100 border border-red-200 px-2 py-0.5 rounded">Telat {{ $telatRiwayat }} hari</span>
                            @else
                                <span class="inline-block mt-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">Tepat waktu</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm font-bold text-slate-800">{{ $pinjamRiwayat->user->name ?? 'User Terhapus' }}</p>
                            <p class="text-xs text-slate-500">{{ $pinjamRiwayat->user->email ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4 align-top">
                            @if($pinjamRiwayat && $pinjamRiwayat->detailPinjam->isNotEmpty())
                                <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                                    @foreach($pinjamRiwayat->detailPinjam as $detail)
                                        <li>{{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} <span class="font-bold text-slate-500">({{ $detail->jumlah }}x)</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-sm text-slate-400 italic">Data alat tidak tersedia</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top text-sm text-slate-600">{{ $pengembalian->kondisi_kembali }}</td>
                        <td class="px-6 py-4 align-top">
                            <span class="text-sm font-bold {{ $pengembalian->denda > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 align-top text-xs text-slate-500">{{ $pengembalian->petugas->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <p class="text-base font-semibold text-slate-700">{{ $search !== '' ? 'Tidak ada riwayat yang cocok' : 'Belum ada riwayat pengembalian' }}</p>
                            <p class="text-sm text-slate-500 mt-1">{{ $search !== '' ? 'Coba ubah kata kunci pencarian.' : 'Pengembalian yang sudah diproses akan muncul di sini.' }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($riwayat->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                {{ $riwayat->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Popup Proses Pengembalian -->
<div id="modalPengembalian" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 max-w-[92vw] shadow-lg rounded-xl bg-white">
        <h3 class="text-lg font-bold text-gray-900 mb-3 border-b pb-2">Form Pengembalian Alat</h3>

        <!-- Detail transaksi yang sedang diproses -->
        <div class="mb-4 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-700 space-y-1">
            <p><span class="text-slate-500">Transaksi:</span> <span id="mdlTrx" class="font-mono font-bold"></span></p>
            <p><span class="text-slate-500">Peminjam:</span> <span id="mdlPeminjam" class="font-bold"></span> <span id="mdlEmail" class="text-slate-500"></span></p>
            <p><span class="text-slate-500">Periode:</span> <span id="mdlPeriode"></span></p>
            <div>
                <span class="text-slate-500">Alat:</span>
                <ul id="mdlAlat" class="list-disc list-inside mt-0.5"></ul>
            </div>
        </div>

        <form id="formPengembalian" method="POST" action="">
            @csrf
            <div id="alertTelat" class="hidden mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded">
                <p class="text-xs text-red-700 font-bold mb-1">⚠️ TRANSAKSI TERLAMBAT</p>
                <p class="text-xs text-red-600">
                    Peminjam telat <span id="textHariTelat"></span> hari.
                    Denda keterlambatan <strong>Rp <span id="textDendaTelat"></span></strong> dihitung otomatis saat disimpan.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kondisi Alat Saat Kembali</label>
                <textarea name="kondisi_kembali" id="inputKondisi" required rows="2" maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm" placeholder="Misal: Lengkap dan berfungsi baik..."></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Denda Kerusakan (Jika Ada)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">Rp</span>
                    <input type="number" name="denda_kerusakan" id="inputDendaKerusakan" value="0" min="0" max="{{ $maksDenda }}" step="1"
                        class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                </div>
            </div>

            <!-- Pratinjau total denda -->
            <div class="mb-5 bg-purple-50 border border-purple-100 rounded-lg p-3 text-xs">
                <div class="flex justify-between text-slate-600"><span>Denda telat</span><span>Rp <span id="sumTelat">0</span></span></div>
                <div class="flex justify-between text-slate-600"><span>Denda kerusakan</span><span>Rp <span id="sumRusak">0</span></span></div>
                <div class="flex justify-between font-bold text-slate-800 border-t border-purple-200 mt-1.5 pt-1.5"><span>Total denda</span><span>Rp <span id="sumTotal">0</span></span></div>
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="tutupModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg text-sm font-semibold hover:bg-gray-300 transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-semibold hover:bg-purple-700 transition shadow-sm">Selesaikan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const fmt = (n) => new Intl.NumberFormat('id-ID').format(n);
    let dendaTelatSaatIni = 0;

    // ---- Tab ----
    function switchTab(tab) {
        document.getElementById('tab-aktif').classList.toggle('hidden', tab !== 'aktif');
        document.getElementById('tab-riwayat').classList.toggle('hidden', tab !== 'riwayat');

        const aktif = 'px-6 py-3 text-sm font-bold text-purple-600 border-b-2 border-purple-600 bg-white';
        const nonaktif = 'px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 bg-gray-50';
        document.getElementById('tab-btn-aktif').className = tab === 'aktif' ? aktif : nonaktif;
        document.getElementById('tab-btn-riwayat').className = tab === 'riwayat' ? aktif : nonaktif;

        // Simpan tab aktif di form pencarian supaya tidak berpindah tab saat mencari
        document.getElementById('input-tab').value = tab;
    }

    document.addEventListener('DOMContentLoaded', function () {
        switchTab(@json($tab));
    });

    // ---- Modal ----
    function hitungTotal() {
        const rusak = Math.max(0, parseInt(document.getElementById('inputDendaKerusakan').value, 10) || 0);
        document.getElementById('sumTelat').innerText = fmt(dendaTelatSaatIni);
        document.getElementById('sumRusak').innerText = fmt(rusak);
        document.getElementById('sumTotal').innerText = fmt(dendaTelatSaatIni + rusak);
    }

    function bukaModalPengembalian(d) {
        document.getElementById('formPengembalian').action = `/petugas/pengembalian/${d.id}/proses`;

        // Detail transaksi
        document.getElementById('mdlTrx').innerText = '#TRX-' + d.id;
        document.getElementById('mdlPeminjam').innerText = d.peminjam;
        document.getElementById('mdlEmail').innerText = '(' + d.email + ')';
        document.getElementById('mdlPeriode').innerText = d.tglPinjam + ' s/d ' + d.tglPlan;
        const ul = document.getElementById('mdlAlat');
        ul.innerHTML = '';
        d.alat.forEach(function (nama) {
            const li = document.createElement('li');
            li.textContent = nama; // textContent: aman dari XSS
            ul.appendChild(li);
        });

        // Peringatan telat
        const alertBox = document.getElementById('alertTelat');
        if (d.hariTelat > 0) {
            document.getElementById('textHariTelat').innerText = d.hariTelat;
            document.getElementById('textDendaTelat').innerText = fmt(d.dendaTelat);
            alertBox.classList.remove('hidden');
        } else {
            alertBox.classList.add('hidden');
        }

        // Reset input + pratinjau total
        document.getElementById('inputKondisi').value = '';
        document.getElementById('inputDendaKerusakan').value = 0;
        dendaTelatSaatIni = d.dendaTelat;
        hitungTotal();

        document.getElementById('modalPengembalian').classList.remove('hidden');
    }

    function tutupModal() {
        document.getElementById('modalPengembalian').classList.add('hidden');
    }

    document.getElementById('inputDendaKerusakan').addEventListener('input', hitungTotal);
    document.getElementById('modalPengembalian').addEventListener('click', function (e) {
        if (e.target === this) tutupModal();
    });
</script>
@endsection