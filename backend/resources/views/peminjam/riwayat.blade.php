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

    <!-- Peringatan keterlambatan -->
    @if($ringkasan['telat'] > 0)
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            Anda memiliki <strong>{{ $ringkasan['telat'] }}</strong> peminjaman yang melewati tenggat. Segera kembalikan alatnya.
            Selama itu belum selesai, Anda tidak bisa mengajukan peminjaman baru.
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Sedang Berjalan</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $ringkasan['berjalan'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Terlambat</p>
            <p class="text-2xl font-bold {{ $ringkasan['telat'] > 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">{{ $ringkasan['telat'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Denda</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">Rp {{ number_format($ringkasan['denda'], 0, ',', '.') }}</p>
        </div>
    </div>

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
                        <th class="py-4 px-6 font-semibold">Tanggal Kembali</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                        <th class="py-4 px-6 font-semibold text-right">Denda</th>
                        <th class="py-4 px-6 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-100">
                    @forelse($peminjamans as $peminjaman)
                    @php
                        $hari = $hariTelat[$peminjaman->id] ?? 0;
                        $berjalan = in_array($peminjaman->status, ['dipinjam', 'telat', 'menunggu_pengembalian'], true);
                        $dendaBerjalan = $berjalan ? $hari * \App\Services\PeminjamanService::DENDA_PER_HARI : 0;
                    @endphp
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
                        <td class="py-4 px-6 text-gray-600">
                            <span class="font-medium block">{{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d M Y') }}</span>
                            <span class="text-xs text-gray-400 block">rencana</span>
                            @if($peminjaman->pengembalian)
                                <span class="text-xs text-emerald-600 font-semibold block mt-1">
                                    Dikembalikan {{ \Carbon\Carbon::parse($peminjaman->pengembalian->tgl_kembali)->format('d M Y') }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6">
                            <x-status-badge :status="$peminjaman->status" />
                            @if($berjalan && $hari > 0)
                                <p class="text-xs text-red-600 font-semibold mt-1.5">Telat {{ $hari }} hari</p>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right">
                            @if($peminjaman->pengembalian)
                                <span class="font-semibold {{ $peminjaman->pengembalian->denda > 0 ? 'text-red-600' : 'text-gray-500' }}">
                                    Rp {{ number_format($peminjaman->pengembalian->denda, 0, ',', '.') }}
                                </span>
                            @elseif($dendaBerjalan > 0)
                                <span class="font-semibold text-red-600">Rp {{ number_format($dendaBerjalan, 0, ',', '.') }}</span>
                                <span class="block text-[11px] text-gray-400">denda berjalan</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right">
                            @if(in_array($peminjaman->status, ['dipinjam', 'telat']))
                                <form action="{{ route('peminjam.kembalikan', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin mengajukan pengembalian alat ini ke Petugas?')">
                                    @csrf
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-end gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                        Kembalikan Alat
                                    </button>
                                </form>

                            @elseif($peminjaman->status == 'diajukan')
                                <form action="{{ route('peminjam.batalkan', $peminjaman->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pengajuan ini? Data akan dihapus permanen.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm flex items-center justify-end gap-2 ml-auto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Batalkan
                                    </button>
                                </form>

                            @elseif($peminjaman->status == 'menunggu_pengembalian')
                                <span class="text-xs text-purple-600 font-medium">Menunggu konfirmasi petugas.<br>Serahkan alat ke loket.</span>

                            @elseif($peminjaman->status == 'dikembalikan')
                                <span class="text-xs text-emerald-600 font-medium">Selesai</span>

                            @elseif($peminjaman->status == 'ditolak')
                                <span class="text-xs text-gray-500 font-medium">Ditolak petugas</span>

                            @else
                                <span class="text-xs text-gray-400 italic">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-500">
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