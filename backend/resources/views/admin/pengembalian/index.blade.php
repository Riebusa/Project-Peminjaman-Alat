@extends('layouts.dev')

@section('title', 'Kelola Pengembalian - Panel Admin')
@section('header-title', 'Riwayat Pengembalian Alat')

@section('content')
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Riwayat Pengembalian</h3>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <!-- Form Search -->
                <form action="{{ route('admin.pengembalian.index') }}" method="GET" class="flex w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam / kondisi..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.pengembalian.index') }}"
                            class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Alat yang Dikembalikan</th>
                        <th class="py-3 px-4 border-b">Tgl Kembali</th>
                        <th class="py-3 px-4 border-b">Kondisi & Denda</th>
                        <th class="py-3 px-4 border-b">Petugas Penerima</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($pengembalians as $kembali)
                    <tr class="hover:bg-gray-50 transition align-top">
                        <td class="py-3 px-4 border-b font-medium text-gray-900">
                            {{ $kembali->peminjaman->user->name ?? 'User Dihapus' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($kembali->peminjaman->detailPinjam as $detail)
                                    <li>
                                        <span class="font-semibold">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span>
                                        <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded">({{ $detail->jumlah }} pcs)</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 px-4 border-b">
                            <span class="font-semibold">{{ $kembali->tgl_kembali }}</span>
                        </td>
                        <td class="py-3 px-4 border-b">
                            <span class="block mb-1 px-2.5 py-1 text-xs font-semibold rounded-full w-max
                                @if($kembali->kondisi_kembali == 'Lengkap & Baik') bg-emerald-100 text-emerald-800
                                @elseif($kembali->kondisi_kembali == 'Rusak Ringan') bg-yellow-100 text-yellow-800
                                @elseif($kembali->kondisi_kembali == 'Hilang') bg-red-100 text-red-900 font-bold border border-red-200
                                @else bg-red-100 text-red-800 @endif">
                                {{ $kembali->kondisi_kembali }}
                            </span>
                            <span class="block text-xs font-semibold text-gray-600">
                                Denda: Rp {{ number_format($kembali->denda, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 border-b text-gray-600">
                            {{ $kembali->petugas->name ?? 'Sistem' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-500">Belum ada riwayat pengembalian.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $pengembalians->links() }}
        </div>
    </div>
@endsection