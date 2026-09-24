@extends('layouts.dev')

@section('title', 'Kelola Pengembalian')
@section('header-title', 'Proses Pengembalian & Denda')

@section('content')
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 text-emerald-800 p-4 rounded-lg shadow-sm text-sm border border-emerald-200">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-800 p-4 rounded-lg shadow-sm text-sm border border-red-200">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- Navigasi Tab -->
        <div class="flex border-b border-gray-200 bg-gray-50 justify-between items-center pr-4">
            <div class="flex">
                <button onclick="switchTab('request')" id="tab-btn-request" class="px-6 py-3 text-sm font-bold text-purple-600 border-b-2 border-purple-600 bg-white">
                    Request Pengembalian (Belum Diproses)
                </button>
                <button onclick="switchTab('riwayat')" id="tab-btn-riwayat" class="px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                    Riwayat Selesai
                </button>
            </div>
            <!-- Tombol Tambah Pengembalian Manual -->
            <a href="{{ route('admin.pengembalian.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tanbah  Pengembalian
            </a>
        </div>

        <!-- TAB 1: Request Pengembalian -->
        <div id="tab-request" class="p-0 block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Peminjam</th>
                        <th class="py-3 px-4 font-semibold">Alat (Jumlah)</th>
                        <th class="py-3 px-4 font-semibold">Rencana Kembali</th>
                        <th class="py-3 px-4 font-semibold bg-purple-50">Proses Pengembalian</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($belumDiproses as $peminjaman)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 font-bold text-gray-900">{{ $peminjaman->user->name ?? 'User Dihapus' }}</td>
                        <td class="py-3 px-4">
                            <ul class="list-disc pl-4">
                                @foreach($peminjaman->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Dihapus' }} ({{ $detail->jumlah }})</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d M Y') }}
                        </td>
                        <td class="py-3 px-4 bg-purple-50/30">
                            <form action="{{ route('admin.pengembalian.terima', $peminjaman->id) }}" method="POST" class="w-64">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="kondisi_kembali" required placeholder="Kondisi Alat (Cth: Baik)" class="w-full text-xs border border-gray-300 p-1.5 rounded focus:ring-1 focus:ring-purple-500">
                                </div>
                                <div class="mb-2">
                                    <input type="number" name="denda_kerusakan" placeholder="Denda Kerusakan (Opsional)" min="0" class="w-full text-xs border border-gray-300 p-1.5 rounded focus:ring-1 focus:ring-purple-500">
                                    <span class="text-[10px] text-gray-500">*Denda telat dihitung otomatis</span>
                                </div>
                                <div class="flex gap-1">
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-1.5 rounded">Terima</button>
                                    <button type="button" onclick="document.getElementById('form-tolak-{{ $peminjaman->id }}').submit();" class="w-1/3 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold py-1.5 rounded border border-red-200">Tolak</button>
                                </div>
                            </form>
                            <!-- Form tersembunyi untuk tolak request -->
                            <form id="form-tolak-{{ $peminjaman->id }}" action="{{ route('admin.pengembalian.tolak', $peminjaman->id) }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="py-8 text-center text-gray-500">Tidak ada request pengembalian saat ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- TAB 2: Riwayat Pengembalian Selesai -->
        <div id="tab-riwayat" class="p-0 hidden overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold">Tgl Pinjam - Kembali</th>
                        <th class="py-3 px-4 font-semibold">Peminjam</th>
                        <th class="py-3 px-4 font-semibold">Alat yang Dikembalikan</th>
                        <th class="py-3 px-4 font-semibold">Kondisi Alat</th>
                        <th class="py-3 px-4 font-semibold">Total Denda</th>
                        <th class="py-3 px-4 font-semibold">Diproses Oleh</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @forelse($sudahDiproses as $pengembalian)
                    <tr class="hover:bg-gray-50">
                        <!-- TAMPILKAN TANGGAL PINJAM & TANGGAL KEMBALI DI SINI -->
                        <td class="py-3 px-4 text-gray-800 text-xs font-medium">
                            <div class="text-gray-900 font-semibold">{{ \Carbon\Carbon::parse($pengembalian->peminjaman->tgl_pinjam)->format('d M Y') }}</div>
                            <div class="text-gray-500">s/d {{ \Carbon\Carbon::parse($pengembalian->tgl_kembali)->format('d M Y') }}</div>
                        </td>
                        <td class="py-3 px-4 font-bold text-gray-900">
                            {{ $pengembalian->peminjaman->user->name ?? 'Dihapus' }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            <ul class="list-disc pl-4">
                                @if($pengembalian->peminjaman && $pengembalian->peminjaman->detailPinjam)
                                    @foreach($pengembalian->peminjaman->detailPinjam as $detail)
                                        <li>{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }} unit)</li>
                                    @endforeach
                                @else
                                    <span class="text-gray-400 italic">Data alat tidak tersedia</span>
                                @endif
                            </ul>
                        </td>
                        <td class="py-3 px-4 text-gray-600">{{ $pengembalian->kondisi_kembali }}</td>
                        <td class="py-3 px-4">
                            <span class="font-bold {{ $pengembalian->denda > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($pengembalian->denda, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-gray-500">{{ $pengembalian->petugas->name ?? 'Admin' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="py-8 text-center text-gray-500">Belum ada riwayat pengembalian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Script Tab -->
    <script>
        function switchTab(tab) {
            document.getElementById('tab-request').classList.toggle('hidden', tab !== 'request');
            document.getElementById('tab-riwayat').classList.toggle('hidden', tab !== 'riwayat');
            
            document.getElementById('tab-btn-request').className = tab === 'request' 
                ? 'px-6 py-3 text-sm font-bold text-purple-600 border-b-2 border-purple-600 bg-white' 
                : 'px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 bg-gray-50';
                
            document.getElementById('tab-btn-riwayat').className = tab === 'riwayat' 
                ? 'px-6 py-3 text-sm font-bold text-purple-600 border-b-2 border-purple-600 bg-white' 
                : 'px-6 py-3 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700 bg-gray-50';
        }
    </script>
@endsection