@extends('layouts.dev')

@section('title', 'Persetujuan Peminjaman')
@section('header-title', 'Persetujuan Peminjaman')

@section('content')
@php
    $tabs = [
        'diajukan' => 'Diajukan',
        'dipinjam' => 'Dipinjam',
        'telat' => 'Telat',
        'menunggu_pengembalian' => 'Menunggu Pengembalian',
        'dikembalikan' => 'Dikembalikan',
        'ditolak' => 'Ditolak',
        'semua' => 'Semua',
    ];
@endphp

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Daftar Pengajuan Peminjaman</h2>
    <p class="text-slate-500 mt-1">Tinjau, setujui, atau tolak permintaan peminjaman alat di bawah ini.</p>
</div>

@if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg text-sm font-medium">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg text-sm font-medium">
        {{ session('error') }}
    </div>
@endif

<!-- Tab Filter Status -->
<div class="mb-4 flex flex-wrap gap-2">
    @foreach($tabs as $key => $label)
        <a href="{{ route('petugas.peminjaman.index', ['status' => $key]) }}"
           class="px-3.5 py-1.5 rounded-lg text-sm font-semibold border transition
           {{ $status === $key ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            {{ $label }}
            @if($key === 'diajukan' && $jumlahDiajukan > 0)
                <span class="ml-1 bg-red-500 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5">{{ $jumlahDiajukan }}</span>
            @endif
        </a>
    @endforeach
</div>

<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-medium">ID / Tanggal</th>
                    <th class="px-6 py-4 font-medium">Peminjam</th>
                    <th class="px-6 py-4 font-medium">Daftar Alat</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($peminjamans as $pinjam)
                    @php
                        $isDiajukan = $pinjam->status === 'diajukan';
                        // Ada alat yang stoknya tidak cukup untuk jumlah yang diminta?
                        $stokKurang = $isDiajukan && $pinjam->detailPinjam->contains(
                            fn ($d) => !$d->alat || $d->alat->stok < $d->jumlah
                        );
                    @endphp
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 align-top">
                        <span class="font-mono text-sm font-bold text-slate-700 block">#TRX-{{ $pinjam->id }}</span>
                        <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($pinjam->tgl_pinjam)->translatedFormat('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <p class="text-sm font-bold text-slate-800">{{ $pinjam->user->name ?? 'User Terhapus' }}</p>
                        <p class="text-xs text-slate-500">Tenggat: {{ \Carbon\Carbon::parse($pinjam->tgl_kembali_plan)->translatedFormat('d M Y') }}</p>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                            @foreach($pinjam->detailPinjam as $detail)
                                @php $kurang = $isDiajukan && (!$detail->alat || $detail->alat->stok < $detail->jumlah); @endphp
                                <li>
                                    {{ $detail->alat->nama_alat ?? 'Alat Terhapus' }}
                                    <span class="font-bold text-slate-500">({{ $detail->jumlah }}x)</span>
                                    @if($isDiajukan && $detail->alat)
                                        <span class="text-xs {{ $kurang ? 'text-red-600 font-bold' : 'text-slate-400' }}">
                                            stok: {{ $detail->alat->stok }}{{ $kurang ? ' (tidak cukup)' : '' }}
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <x-status-badge :status="$pinjam->status" />
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        @if($isDiajukan)
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('petugas.peminjaman.setujui', $pinjam->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menyetujui? Stok alat akan otomatis berkurang.');">
                                    @csrf
                                    <button type="submit"
                                            @disabled($stokKurang)
                                            title="{{ $stokKurang ? 'Stok tidak mencukupi' : '' }}"
                                            class="text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition {{ $stokKurang ? 'bg-slate-300 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                        Setujui
                                    </button>
                                </form>
                                <form action="{{ route('petugas.peminjaman.tolak', $pinjam->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menolak pengajuan ini?');">
                                    @csrf
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="text-xs text-slate-400 font-medium italic">Sudah Diproses</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-base font-semibold text-slate-700">
                            {{ $status === 'diajukan' ? 'Tidak ada pengajuan' : 'Tidak ada data' }}
                        </p>
                        <p class="text-sm text-slate-500 mt-1">
                            {{ $status === 'diajukan' ? 'Saat ini tidak ada permintaan peminjaman alat yang menunggu.' : 'Tidak ada transaksi dengan status ini.' }}
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($peminjamans->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $peminjamans->links() }}
        </div>
    @endif
</div>
@endsection