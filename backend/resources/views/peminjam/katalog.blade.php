@extends('layouts.peminjam')

@section('title', 'Katalog Alat - Peminjam')
@section('header-title', 'Katalog Peminjaman Alat')

@section('content')
    <!-- Notifikasi Sukses / Error -->
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Baris Pencarian -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <form action="{{ route('peminjam.katalog') }}" method="GET" class="flex w-full md:w-96">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama alat..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                Cari
            </button>
            @if(request('search'))
                <a href="{{ route('peminjam.katalog') }}" class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">Reset</a>
            @endif
        </form>
    </div>

    <!-- Grid Katalog Alat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($alats as $alat)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between transition-transform hover:-translate-y-1 duration-200">
            <div>
                <!-- Gambar Alat -->
                @if($alat->gambar)
                    <img src="{{ asset('storage/' . $alat->gambar) }}" alt="{{ $alat->nama_alat }}" class="w-full h-48 object-cover bg-gray-100">
                @else
                    <div class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400 text-xs font-semibold uppercase">Tidak Ada Gambar</div>
                @endif

                <div class="p-4">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 rounded-full border border-blue-100">
                        {{ $alat->kategori->nama_kategori ?? 'Umum' }}
                    </span>
                    <h4 class="font-bold text-gray-900 text-base mt-2">{{ $alat->nama_alat }}</h4>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $alat->deskripsi ?? 'Tidak ada deskripsi tersedia untuk alat ini.' }}</p>
                    
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                        <span class="text-gray-600 font-medium">Stok Tersedia:</span>
                        <span class="font-bold {{ $alat->stok > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $alat->stok }} Unit
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Ajukan Pinjam Langsung di Kartu -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <form action="{{ route('peminjam.ajukan') }}" method="POST">
                    @csrf
                    <!-- ID alat dikirim ke controller dalam bentuk array -->
                    <input type="hidden" name="alat_id[]" value="{{ $alat->id }}">
                    
                    <div class="mb-3 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Jml Pinjam:</label>
                            <input type="number" name="jumlah[]" value="1" min="1" max="{{ $alat->stok }}" required
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tgl Kembali:</label>
                            <input type="date" name="tgl_kembali_plan" required min="{{ date('Y-m-d') }}"
                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-md transition shadow-sm flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Ajukan Pinjaman
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 flex flex-col items-center justify-center text-gray-500 bg-white rounded-lg border border-gray-200 shadow-sm">
            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            <p class="text-lg font-medium text-gray-600">Belum ada alat yang tersedia saat ini.</p>
            <p class="text-sm mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $alats->links() }}
    </div>
@endsection