@extends('layouts.dev')

@section('title', 'Dashboard Petugas')
@section('header-title', 'Panel Operasional Petugas')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-800">Selamat Bertugas, {{ Auth::user()->name }}!</h2>
    <p class="text-slate-500 mt-1">Pilih menu di bawah ini untuk memulai tugas operasional Anda hari ini.</p>
</div>

<!-- Grid 3 Tugas Utama -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <!-- TUGAS 1 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-2">Persetujuan Peminjaman</h3>
        <p class="text-sm text-slate-500 mb-4 h-10">Tinjau dan setujui permintaan peminjaman alat dari pengguna (user).</p>
        
        @if($peminjamanDiajukan > 0)
            <div class="mb-4 bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-2 rounded border border-emerald-100 inline-block">
                {{ $peminjamanDiajukan }} Pengajuan Baru Menunggu!
            </div>
        @endif

        <a href="{{ route('petugas.peminjaman.index') }}" class="block w-full text-center bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-lg transition-colors">
            Buka Menu Persetujuan
        </a>
    </div>

    <!-- TUGAS 2 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-2">Kelola Pengembalian</h3>
        <p class="text-sm text-slate-500 mb-4 h-10">Pantau tenggat waktu alat dan proses serah-terima alat yang dikembalikan.</p>
        
        @if($peminjamanAktif > 0)
            <div class="mb-4 bg-purple-50 text-purple-700 text-xs font-bold px-3 py-2 rounded border border-purple-100 inline-block">
                Ada {{ $peminjamanAktif }} Alat Sedang Dipinjam
            </div>
        @endif

        <a href="{{ route('petugas.pengembalian.index') }}" class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 rounded-lg transition-colors">
            Proses Pengembalian
        </a>
    </div>

    <!-- TUGAS 3 -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-2">Cetak Laporan</h3>
        <p class="text-sm text-slate-500 mb-4 h-10">Rekapitulasi data transaksi peminjaman harian, bulanan, atau tahunan.</p>
        
        <div class="mb-4 h-[34px]"></div> <!-- Spacer biar sejajar dengan kartu lain -->

        <a href="{{ route('petugas.laporan.index') }}" class="block w-full text-center bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-lg transition-colors">
            Buat Laporan
        </a>
    </div>

</div>
@endsection