@extends('layouts.peminjam')

@section('title', 'Riwayat Peminjaman - Peminjam')
@section('header-title', 'Riwayat & Status Peminjaman Saya')

@section('content')
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

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-white flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Daftar Pengajuan Saya</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-4 px-6 font-semibold">Alat yang Dipinjam</th>
                        <th class="py-4 px-6 font-semibold">Tanggal Pinjam</th>
                        <th class="py-4 px-6 font-semibold">Rencana Kembali</th>
                        <th class="py-4 px-6 font-semibold text-center">Status</th>
                        <th class="py-4 px-6 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-100">
                    @forelse($peminjamans as $peminjaman)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 px-6">
                            <ul class="list-disc pl-4 space-y-1">
                                @foreach($peminjaman->detailPinjam as $detail)
                                    <li>
                                        <span class="font-semibold text-gray-900">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span> 
                                        <span class="text-xs text-gray-500 font-medium">({{ $detail->jumlah }} unit)</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-4 px-6 text-gray-600">
                            {{ \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d M Y') }}
                        </td>
                        <td class="py-4 px-6 text-gray-600 font-medium">
                            {{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d M Y') }}
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="px-3 py-1.5 text-xs font-bold rounded-md uppercase tracking-wide
                                @if($peminjaman->status == 'diajukan') bg-amber-100 text-amber-800
                                @elseif($peminjaman->status == 'dipinjam') bg-blue-100 text-blue-800
                                @elseif($peminjaman->status == 'menunggu_pengembalian') bg-purple-100 text-purple-800
                                @elseif($peminjaman->status == 'dikembalikan') bg-emerald-100 text-emerald-800
                                @elseif($peminjaman->status == 'telat') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ str_replace('_', ' ', $peminjaman->status) }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            @if(in_array($peminjaman->status, ['dipinjam', 'telat']))
                                <!-- Tombol Kembalikan (Sudah ada) -->
                                <form action="{{ route('peminjam.kembalikan', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin mengajukan pengembalian alat ini ke Petugas?')">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-end gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                        Kembalikan Alat
                                    </button>
                                </form>
                                
                            @elseif($peminjaman->status == 'diajukan')
                                <!-- TOMBOL BATALKAN (BARU) -->
                                <form action="{{ route('peminjam.batalkan', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengajuan ini? Data akan dihapus permanen.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-end gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Batalkan
                                    </button>
                                </form>
                                
                            @else
                                <span class="text-xs text-gray-400 italic">Tidak ada aksi tersedia</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <p>Anda belum pernah meminjam alat apapun.</p>
                                <a href="{{ route('peminjam.katalog') }}" class="text-blue-600 hover:underline mt-2 text-sm font-semibold">Lihat Katalog Alat</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($peminjamans->hasPages())
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $peminjamans->links() }}   
        </div>
        @endif
    </div>
@endsection