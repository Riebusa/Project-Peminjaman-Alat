@extends('layouts.dev')

@section('title', 'Kelola Peminjaman - Panel Admin')
@section('header-title', 'Manajemen Transaksi Peminjaman')

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
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Transaksi Peminjaman</h3>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <!-- Form Search -->
                <form action="{{ route('admin.peminjaman.index') }}" method="GET" class="flex w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama peminjam / status..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                        Cari
                    </button>
                    @if(request('search'))
                        <a href="{{ route('admin.peminjaman.index') }}"
                            class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">
                            Reset
                        </a>
                    @endif
                </form>

                <!-- Tombol Tambah -->
                <a href="{{ route('admin.peminjaman.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap">
                    + Tambah Peminjaman
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 text-sm uppercase tracking-wider">
                        <th class="py-3 px-4 border-b">Peminjam</th>
                        <th class="py-3 px-4 border-b">Alat yang Dipinjam</th>
                        <th class="py-3 px-4 border-b">Tgl Pinjam / Rencana Kembali</th>
                        <th class="py-3 px-4 border-b">Status</th>
                        <th class="py-3 px-4 border-b">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    @forelse($peminjamans as $peminjaman)
                    <tr class="hover:bg-gray-50 transition align-top">
                        <td class="py-3 px-4 border-b font-medium text-gray-900">
                            {{ $peminjaman->user->name ?? 'User Dihapus' }}
                        </td>
                        <td class="py-3 px-4 border-b">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($peminjaman->detailPinjam as $detail)
                                    <li>
                                        <span class="font-semibold">{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}</span>
                                        <span class="text-xs bg-gray-200 px-1.5 py-0.5 rounded">({{ $detail->jumlah }} pcs)</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 px-4 border-b text-xs text-gray-600">
                            <span class="block">Pinjam: {{ $peminjaman->tgl_pinjam }}</span>
                            <span class="block font-semibold">Rencana: {{ $peminjaman->tgl_kembali_plan }}</span>
                        </td>
                        <td class="py-3 px-4 border-b">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                @if($peminjaman->status == 'diajukan') bg-yellow-100 text-yellow-800
                                @elseif($peminjaman->status == 'dipinjam') bg-blue-100 text-blue-800
                                @elseif($peminjaman->status == 'selesai') bg-emerald-100 text-emerald-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($peminjaman->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 border-b">
                            <div class="flex flex-col space-y-2">
                                <!-- Form Ubah Status Cepat -->
                                <form action="{{ route('admin.peminjaman.updateStatus', $peminjaman->id) }}" method="POST" class="flex items-center space-x-1">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none w-full">
                                        <option value="diajukan" {{ $peminjaman->status == 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                                        <option value="dipinjam" {{ $peminjaman->status == 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                                        <option value="selesai" {{ $peminjaman->status == 'dikembalikan' ? 'selected' : '' }}>Dikembalikan</option>
                                        <option value="telat" {{ $peminjaman->status == 'telat' ? 'selected' : '' }}>Telat</option>
                                    </select>
                                </form>

                                <!-- TOMBOL PROSES KEMBALI (Ditambahkan di sini) -->
                                @if(in_array($peminjaman->status, ['dipinjam', 'telat']))
                                    <button type="button" onclick="openModal('modalKembali{{ $peminjaman->id }}')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1 rounded text-xs font-semibold transition w-full text-center">
                                        Proses Kembali
                                    </button>
                                @endif

                                <!-- Tombol Hapus -->
                                <form action="{{ route('admin.peminjaman.destroy', $peminjaman->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus data peminjaman ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold transition w-full">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL PENGEMBALIAN TAILWIND (Ditambahkan di sini) -->
                    @if(in_array($peminjaman->status, ['dipinjam', 'telat']))
                    <div id="modalKembali{{ $peminjaman->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <!-- Background Overlay -->
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('modalKembali{{ $peminjaman->id }}')"></div>
                            
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                            
                            <!-- Panel Modal -->
                            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                <form action="{{ route('admin.peminjaman.kembali', $peminjaman->id) }}" method="POST">
                                    @csrf
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Proses Pengembalian Alat</h3>
                                        <div class="mt-4">
                                            <p class="text-sm text-gray-500 mb-4">Peminjam: <strong class="text-gray-900">{{ $peminjaman->user->name ?? 'User Dihapus' }}</strong></p>
                                            
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Alat Saat Dikembalikan</label>
                                                <select name="kondisi_kembali" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                                                    <option value="Lengkap & Baik">Lengkap & Baik</option>
                                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                                    <option value="Rusak Berat">Rusak Berat</option>
                                                    <option value="Hilang">Hilang</option>
                                                </select>
                                            </div>
            
                                            <div class="mb-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Denda (Rp) - <i class="text-gray-400 font-normal">Isi 0 jika tidak ada</i></label>
                                                <input type="number" name="denda" value="0" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-500" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm">
                                            Simpan & Selesaikan
                                        </button>
                                        <button type="button" onclick="closeModal('modalKembali{{ $peminjaman->id }}')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-500">Belum ada data peminjaman.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $peminjamans->links() }}
        </div>
    </div>

    <!-- Script Sederhana untuk Membuka/Menutup Modal -->
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
@endsection