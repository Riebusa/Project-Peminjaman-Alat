@extends('layouts.dev')

@section('title', 'Cetak Laporan - Panel Petugas')
@section('header-title', 'Laporan Peminjaman Alat')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Cetak Laporan Transaksi</h2>
    <p class="text-slate-500 mt-1">Gunakan form di bawah ini untuk memfilter data rekapitulasi peminjaman sebelum dicetak.</p>
</div>

<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 max-w-3xl">
    <!-- Form ini disiapkan menggunakan method GET agar URL menampung parameter filter -->
    <form action="#" method="GET">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-5">
            <!-- Dari Tanggal -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Periode Mulai</label>
                <input type="date" name="start_date" 
                       value="{{ request('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d')) }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" required>
            </div>
            
            <!-- Sampai Tanggal -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Periode Akhir</label>
                <input type="date" name="end_date" 
                       value="{{ request('end_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" 
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm" required>
            </div>
        </div>

        <!-- Filter Status Transaksi -->
        <div class="mb-8">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Status Transaksi</label>
            <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 text-sm">
                <option value="semua">Semua Status (Lengkap)</option>
                <option value="dikembalikan">Telah Selesai / Dikembalikan</option>
                <option value="dipinjam">Sedang Dipinjam Aktif</option>
                <option value="telat">Terlambat Dikembalikan</option>
            </select>
        </div>

        <div class="flex items-center space-x-3 border-t border-gray-100 pt-5">
            <!-- Tombol Tampilkan Data -->
            <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-colors flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Tampilkan Data
            </button>
            
            <!-- Tombol Cetak Dokumen -->
            <button type="button" onclick="alert('Fitur export ke PDF / Excel akan segera ditambahkan pada tahap selanjutnya!')" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 px-5 rounded-lg text-sm transition-colors flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Dokumen
            </button>
        </div>
    </form>
</div>
@endsection