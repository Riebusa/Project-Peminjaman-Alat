@extends('layouts.dev')

@section('title', 'Kelola Peminjaman')
@section('header-title', 'Manajemen Persetujuan & Peminjaman Alat')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-800 p-4 rounded-lg shadow-sm text-sm border border-emerald-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-800 p-4 rounded-lg shadow-sm text-sm border border-red-200">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Navigasi Tab Sederhana & Tombol Tambah -->
        <div class="flex justify-between items-center border-b border-gray-200 bg-gray-50 pr-4">
            <div class="flex">
                <button onclick="switchTab('aktif')" id="tab-btn-aktif" class="px-6 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition">
                    Transaksi Aktif & Menunggu Persetujuan
                </button>
                <button onclick="switchTab('selesai')" id="tab-btn-selesai" class="px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 transition">
                    Riwayat Selesai / Ditolak
                </button>
            </div>
            
            <!-- TOMBOL TAMBAH PEMINJAMAN MANUAL -->
            <a href="{{ route('admin.peminjaman.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Peminjaman
            </a>
        </div>

        <!-- TAB 1: Transaksi Aktif -->
        <div id="tab-aktif" class="p-0 block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Peminjam</th>
                        <th class="py-3 px-4 font-semibold">Alat (Jumlah)</th>
                        <th class="py-3 px-4 font-semibold">Tgl Pinjam - Kembali</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($peminjamanAktif as $peminjaman)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <span class="font-bold text-gray-900">{{ $peminjaman->user->name ?? 'User Dihapus' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <ul class="list-disc pl-4">
                                @foreach($peminjaman->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Dihapus' }} ({{ $detail->jumlah }})</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d M') }} s/d {{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d M Y') }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-md uppercase 
                                {{ $peminjaman->status == 'diajukan' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ str_replace('_', ' ', $peminjaman->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            @if($peminjaman->status == 'diajukan')
                                <div class="flex flex-col gap-1 items-end">
                                    <form action="{{ route('admin.peminjaman.setujui', $peminjaman->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded font-bold w-full">Setujui</button>
                                    </form>
                                    <form action="{{ route('admin.peminjaman.tolak', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menolak?')">
                                        @csrf
                                        <button type="submit" class="bg-red-50 text-red-600 border border-red-200 text-xs px-3 py-1.5 rounded font-bold w-full">Tolak</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Lihat di menu Pengembalian</span>
                            @endif

                            <form action="{{ route('admin.peminjaman.destroy', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus transaksi ini? Jika alat sudah berstatus dipinjam, stok akan otomatis dikembalikan ke gudang.')" class="w-full mt-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 border border-gray-300 hover:border-red-300 text-xs px-3 py-1.5 rounded font-bold w-full transition flex items-center justify-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="py-8 text-center text-gray-500">Tidak ada transaksi aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TAB 2: Transaksi Selesai -->
        <div id="tab-selesai" class="p-0 hidden overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Peminjam</th>
                        <th class="py-3 px-4 font-semibold">Alat (Jumlah)</th>
                        <th class="py-3 px-4 font-semibold">Status Akhir</th>
                        <!-- Tambahkan Kolom Aksi -->
                        <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($peminjamanSelesai as $peminjaman)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-bold text-gray-900">{{ $peminjaman->user->name ?? 'User Dihapus' }}</td>
                        <td class="py-3 px-4">
                            <ul class="list-disc pl-4">
                                @foreach($peminjaman->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Dihapus' }} ({{ $detail->jumlah }})</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-md uppercase 
                                {{ $peminjaman->status == 'dikembalikan' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $peminjaman->status }}
                            </span>
                        </td>
                        <!-- Tambahkan Tombol Hapus -->
                        <td class="py-3 px-4 text-right">
                            <form action="{{ route('admin.peminjaman.destroy', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus riwayat ini secara permanen?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-end gap-1 ml-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">Belum ada riwayat selesai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script Sederhana untuk Tab -->
    <script>
        function switchTab(tab) {
            document.getElementById('tab-aktif').classList.toggle('hidden', tab !== 'aktif');
            document.getElementById('tab-selesai').classList.toggle('hidden', tab !== 'selesai');
            
            document.getElementById('tab-btn-aktif').className = tab === 'aktif' 
                ? 'px-6 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-white' 
                : 'px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 bg-gray-50';
                
            document.getElementById('tab-btn-selesai').className = tab === 'selesai' 
                ? 'px-6 py-3 text-sm font-bold text-blue-600 border-b-2 border-blue-600 bg-white' 
                : 'px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 bg-gray-50';
        }
    </script>
@endsection