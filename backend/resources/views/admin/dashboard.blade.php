@extends('layouts.dev') <!-- Sesuaikan dengan nama layout Anda -->

@section('content')
<!-- Header Dashboard Minimalis -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 border-b border-slate-200 pb-5">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard Overview</h2>
        <p class="text-slate-500 text-sm mt-1">
            Selamat datang kembali, <span class="font-semibold text-slate-700">{{ Auth::user()->name ?? 'Admin' }}</span>.
        </p>
    </div>
    <div class="mt-4 sm:mt-0 text-right">
        <p class="text-sm font-medium text-slate-500">
            <svg class="w-4 h-4 inline-block mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
        </p>
    </div>
</div>

<!-- Kartu Statistik (Gaya Enterprise: Clean, Border Halus, Icon Kecil di Pojok) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5 mb-8">
    
    <!-- Card 1 -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Alat</h3>
            <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        </div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalAlat }}</p>
    </div>
    
    <!-- Card 2 -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Stok</h3>
            <svg class="w-5 h-5 text-slate-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
        </div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalStok }}</p>
    </div>

    <!-- Card 3 -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori</h3>
            <svg class="w-5 h-5 text-slate-400 group-hover:text-purple-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
        </div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalKategori }}</p>
    </div>

    <!-- Card 4 -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total User</h3>
            <svg class="w-5 h-5 text-slate-400 group-hover:text-orange-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalUser }}</p>
    </div>

    <!-- Card 5 -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm relative overflow-hidden group">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Transaksi</h3>
            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
        </div>
        <p class="text-3xl font-extrabold text-slate-800">{{ $totalPeminjaman }}</p>
    </div>
</div>

<!-- Widget Data Tabel (Terlihat lebih sistematis) -->
<div class="bg-white border border-slate-200 rounded-lg shadow-sm">
    <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50/50 rounded-t-lg">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 rounded-full {{ $peminjamanTelat->count() > 0 ? 'bg-red-500 animate-pulse' : 'bg-slate-300' }}"></div>
            <h3 class="font-bold text-slate-800">Need Action: Peminjaman Terlambat</h3>
        </div>
        @if($peminjamanTelat->count() > 0)
            <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-md">
                {{ $peminjamanTelat->count() }} Terlambat
            </span>
        @endif
    </div>
    
    <div class="overflow-x-auto">
        @if($peminjamanTelat->count() > 0)
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-slate-100 text-xs text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">ID Transaksi</th>
                        <th class="px-6 py-4 font-medium">Peminjam</th>
                        <th class="px-6 py-4 font-medium">Tenggat Waktu</th>
                        <th class="px-6 py-4 font-medium">Status Telat</th>
                        <th class="px-6 py-4 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($peminjamanTelat as $pinjam)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-slate-700">#TRX-{{ str_pad($pinjam->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-800">{{ $pinjam->user->name ?? 'User Unknown' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-600">{{ \Carbon\Carbon::parse($pinjam->tgl_kembali_plan)->translatedFormat('d M Y') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="inline-flex items-center gap-1.5 bg-red-50 px-2.5 py-1 rounded-md border border-red-100">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-xs font-bold text-red-700">Telat {{ (int) \Carbon\Carbon::parse($pinjam->tgl_kembali_plan)->diffInDays(now()) }} Hari</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.peminjaman.index') }}" class="inline-block px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-12 flex flex-col items-center justify-center">
                <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <p class="text-base font-semibold text-slate-700">Semua Terkendali</p>
                <p class="text-sm text-slate-500 mt-1">Tidak ada peminjaman yang melewati batas tenggat waktu.</p>
            </div>
        @endif
    </div>
</div>
@endsection