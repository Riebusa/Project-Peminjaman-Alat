@extends('layouts.dev')

@section('title', 'Kelola Pengembalian Alat')
@section('header-title', 'Kelola Pengembalian Alat')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Daftar Peminjaman Aktif</h2>
    <p class="text-slate-500 mt-1">Pantau alat yang sedang dipinjam dan proses pengembaliannya di sini.</p>
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
                    <th class="px-6 py-4 font-medium">Transaksi / Waktu</th>
                    <th class="px-6 py-4 font-medium">Data Peminjam</th>
                    <th class="px-6 py-4 font-medium">Alat yang Dibawa</th>
                    <th class="px-6 py-4 font-medium">Status Waktu</th>
                    <th class="px-6 py-4 text-center font-medium w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($peminjamanAktif as $pinjam)
                    @php
                        // Menghitung apakah peminjaman ini terlambat
                        $tglKembali = \Carbon\Carbon::parse($pinjam->tgl_kembali_plan)->endOfDay();
                        $sekarang = \Carbon\Carbon::now();
                        $isTerlambat = $sekarang->gt($tglKembali);
                        $hariTelat = $isTerlambat ? $sekarang->diffInDays($tglKembali) : 0;
                    @endphp
                <tr class="hover:bg-slate-50 transition-colors {{ $isTerlambat ? 'bg-red-50/30' : '' }}">
                    <td class="px-6 py-4 align-top">
                        <span class="font-mono text-sm font-bold text-slate-700 block">#TRX-{{ $pinjam->id }}</span>
                        <span class="text-xs text-slate-500">Mulai: {{ \Carbon\Carbon::parse($pinjam->tgl_pinjam)->translatedFormat('d M Y') }}</span>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <p class="text-sm font-bold text-slate-800">{{ $pinjam->user->name ?? 'User Terhapus' }}</p>
                        <p class="text-xs text-slate-500">{{ $pinjam->user->email ?? '-' }}</p>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <ul class="list-disc list-inside text-sm text-slate-700 space-y-1">
                            @if($pinjam->detailPinjam)
                                @foreach($pinjam->detailPinjam as $detail)
                                    <li>{{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} <span class="font-bold text-slate-500">({{ $detail->jumlah }}x)</span></li>
                                @endforeach
                            @endif
                        </ul>
                    </td>
                    <td class="px-6 py-4 align-top">
                        @if($isTerlambat)
                            <div class="inline-flex items-center gap-1.5 bg-red-100 text-red-700 px-2.5 py-1 rounded-md border border-red-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-xs font-bold uppercase tracking-wider">Telat {{ (int)$hariTelat }} Hari</span>
                            </div>
                            <p class="text-[11px] text-red-500 mt-1 font-medium">Tenggat: {{ $tglKembali->translatedFormat('d M Y') }}</p>
                        @else
                            <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md border border-blue-100">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-xs font-bold uppercase tracking-wider">Aman</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">Sisa waktu: {{ (int)$sekarang->diffInDays($tglKembali) }} Hari</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 align-top text-center">
                        <!-- Tombol untuk membuka Modal Pengembalian -->
                        <button onclick="bukaModalPengembalian({{ $pinjam->id }}, {{ $isTerlambat ? 'true' : 'false' }}, {{ (int)$hariTelat }})" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm transition w-full">
                            Proses
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-base font-semibold text-slate-700">Semua Alat Telah Kembali</p>
                        <p class="text-sm text-slate-500 mt-1">Saat ini tidak ada alat yang sedang dipinjam oleh user.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Popup Proses Pengembalian -->
<div id="modalPengembalian" class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center">
    <div class="relative mx-auto p-5 border w-96 shadow-lg rounded-xl bg-white">
        <div class="mt-2">
            <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Form Pengembalian Alat</h3>
            
            <form id="formPengembalian" method="POST" action="">
                @csrf
                <!-- Peringatan Keterlambatan (Muncul Otomatis via JS jika telat) -->
                <div id="alertTelat" class="hidden mb-4 bg-red-50 border-l-4 border-red-500 p-3 rounded">
                    <p class="text-xs text-red-700 font-bold mb-1">⚠️ TRANSAKSI TERLAMBAT</p>
                    <p class="text-xs text-red-600">Peminjam telat <span id="textHariTelat"></span> hari. Pastikan menagih denda jika ada aturan yang berlaku.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kondisi Alat Saat Kembali</label>
                    <textarea name="kondisi_kembali" required rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm" placeholder="Misal: Lengkap dan berfungsi baik..."></textarea>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Denda (Jika Ada)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">Rp</span>
                        <input type="number" name="denda" value="0" min="0" class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="tutupModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg text-sm font-semibold hover:bg-gray-300 transition">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm font-semibold hover:bg-purple-700 transition shadow-sm">Selesaikan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function bukaModalPengembalian(id, isTerlambat, hariTelat) {
        // 1. Atur Action URL Form
        const form = document.getElementById('formPengembalian');
        form.action = `/petugas/pengembalian/${id}/proses`;
        
        // 2. Atur Peringatan Telat
        const alertBox = document.getElementById('alertTelat');
        if(isTerlambat) {
            alertBox.classList.remove('hidden');
            document.getElementById('textHariTelat').innerText = hariTelat;
        } else {
            alertBox.classList.add('hidden');
        }

        // 3. Tampilkan Modal
        document.getElementById('modalPengembalian').classList.remove('hidden');
    }

    function tutupModal() {
        document.getElementById('modalPengembalian').classList.add('hidden');
    }
</script>
@endsection