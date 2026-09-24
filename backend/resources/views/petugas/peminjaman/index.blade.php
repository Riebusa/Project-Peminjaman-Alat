@extends('layouts.dev')

@section('title', 'Persetujuan Peminjaman')
@section('header-title', 'Persetujuan Peminjaman')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Daftar Pengajuan Peminjaman</h2>
    <p class="text-slate-500 mt-1">Tinjau dan setujui permintaan peminjaman alat di bawah ini.</p>
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
                <!-- PERHATIKAN: Variabel yang digunakan adalah $peminjamans -->
                @forelse($peminjamans as $pinjam)
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
                            <!-- PERHATIKAN: Relasinya menggunakan detailPinjam -->
                            @if($pinjam->detailPinjam)
                                @foreach($pinjam->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} <span class="font-bold text-slate-500">({{ $detail->jumlah }}x)</span></li>
                                @endforeach
                            @endif
                        </ul>
                    </td>
                    <td class="px-6 py-4 align-top">
                        @if($pinjam->status == 'diajukan')
                            <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2.5 py-1 rounded-md border border-orange-200 uppercase">Diajukan</span>
                        @elseif($pinjam->status == 'dipinjam')
                            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-md border border-blue-200 uppercase">Dipinjam</span>
                        @elseif($pinjam->status == 'dikembalikan')
                            <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2.5 py-1 rounded-md border border-emerald-200 uppercase">Dikembalikan</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-md border border-red-200 uppercase">{{ $pinjam->status }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 align-top text-right">
                        @if($pinjam->status == 'diajukan')
                            <form action="{{ route('petugas.peminjaman.setujui', $pinjam->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menyetujui? Stok alat akan otomatis berkurang.');">
                                @csrf
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition">
                                    Setujui
                                </button>
                            </form>
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
                        <p class="text-base font-semibold text-slate-700">Tidak ada pengajuan</p>
                        <p class="text-sm text-slate-500 mt-1">Saat ini tidak ada permintaan peminjaman alat yang masuk.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection