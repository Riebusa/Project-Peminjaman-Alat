@extends('layouts.dev')

@section('title', 'Cetak Laporan - Panel Petugas')
@section('header-title', 'Laporan Peminjaman Alat')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Cetak Laporan Transaksi</h2>
    <p class="text-slate-500 mt-1">Filter data berdasarkan tanggal pinjam dan status, lalu tampilkan atau cetak ke PDF.</p>
</div>

@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm font-medium max-w-3xl">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 max-w-3xl mb-8">
    <form action="{{ route('petugas.laporan.index') }}" method="GET">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Periode Mulai</label>
                <input type="date" name="start_date"
                       value="{{ request('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d')) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Periode Akhir</label>
                <input type="date" name="end_date"
                       value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" required>
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Status Transaksi</label>
            @php $statusDipilih = request('status', 'semua'); @endphp
            <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="semua" @selected($statusDipilih === 'semua')>Semua Status (Lengkap)</option>
                <option value="diajukan" @selected($statusDipilih === 'diajukan')>Diajukan</option>
                <option value="dipinjam" @selected($statusDipilih === 'dipinjam')>Sedang Dipinjam</option>
                <option value="telat" @selected($statusDipilih === 'telat')>Terlambat</option>
                <option value="menunggu_pengembalian" @selected($statusDipilih === 'menunggu_pengembalian')>Menunggu Pengembalian</option>
                <option value="dikembalikan" @selected($statusDipilih === 'dikembalikan')>Telah Dikembalikan</option>
                <option value="ditolak" @selected($statusDipilih === 'ditolak')>Ditolak</option>
            </select>
        </div>

        <div class="flex items-center space-x-3 border-t border-gray-100 pt-5">
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-colors flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Tampilkan Data
            </button>

            <!-- Mengirim filter yang sama ke route PDF, dibuka di tab baru -->
            <button type="submit" formaction="{{ route('petugas.laporan.pdf') }}" formtarget="_blank" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-colors flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak PDF
            </button>
        </div>
    </form>
</div>

{{-- ========== HASIL REKAP (muncul setelah "Tampilkan Data") ========== --}}
@if(!is_null($peminjamans))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Total Transaksi</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">{{ $ringkasan['total'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Total Denda</p>
            <p class="text-2xl font-bold text-slate-800 mt-1">Rp {{ number_format($ringkasan['total_denda'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-medium mb-2">Per Status</p>
            <div class="flex flex-wrap gap-1.5">
                @forelse($ringkasan['per_status'] as $status => $jumlah)
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-slate-100 text-slate-700 uppercase">{{ str_replace('_', ' ', $status) }}: {{ $jumlah }}</span>
                @empty
                    <span class="text-sm text-slate-400">-</span>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="px-4 py-3 font-medium">No</th>
                        <th class="px-4 py-3 font-medium">Peminjam</th>
                        <th class="px-4 py-3 font-medium">Alat</th>
                        <th class="px-4 py-3 font-medium">Tgl Pinjam</th>
                        <th class="px-4 py-3 font-medium">Tenggat</th>
                        <th class="px-4 py-3 font-medium">Tgl Kembali</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($peminjamans as $p)
                        @php
                            $warna = match($p->status) {
                                'diajukan' => 'bg-amber-100 text-amber-800',
                                'telat' => 'bg-red-100 text-red-800',
                                'menunggu_pengembalian' => 'bg-purple-100 text-purple-800',
                                'dikembalikan' => 'bg-emerald-100 text-emerald-800',
                                'ditolak' => 'bg-slate-200 text-slate-700',
                                default => 'bg-blue-100 text-blue-800',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $p->user->name ?? 'User Terhapus' }}</p>
                                <p class="text-xs text-slate-500">#TRX-{{ $p->id }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                @foreach($p->detailPinjam as $detail)
                                    <div>{{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} <span class="text-slate-500 font-semibold">({{ $detail->jumlah }}x)</span></div>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ \Carbon\Carbon::parse($p->tgl_pinjam)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ \Carbon\Carbon::parse($p->tgl_kembali_plan)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $p->pengembalian ? \Carbon\Carbon::parse($p->pengembalian->tgl_kembali)->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-md uppercase {{ $warna }}">{{ str_replace('_', ' ', $p->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                {{ $p->pengembalian ? 'Rp ' . number_format($p->pengembalian->denda, 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">Tidak ada transaksi pada periode dan status yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection