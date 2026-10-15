@extends('layouts.dev')

@section('title', 'Tambah Pengembalian - Panel Admin')
@section('header-title', 'Form Pengembalian Alat')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .ts-wrapper .ts-control {
        border: 1px solid #d1d5db !important; 
        padding: 0.5rem 0.75rem !important;   
        border-radius: 0.5rem !important;     
        background-color: #ffffff !important;
        box-shadow: none !important;
        font-size: 0.875rem !important; 
        min-height: 38px !important; 
        display: flex;
        align-items: center;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #9333ea !important; 
        box-shadow: 0 0 0 2px rgba(147, 51, 234, 0.5) !important; 
        outline: none !important;
    }
    .ts-dropdown {
        font-size: 0.875rem !important;
        border-radius: 0.5rem !important;
    }
</style>

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('admin.pengembalian.storeManual') }}" method="POST">
        @csrf

        <!-- 1. PILIH USER DULU -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Pilih Peminjam (User)</label>
            <select id="user_select" class="w-full" autocomplete="off">
                <option value="">Ketik nama peminjam...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>

        <!-- 2. PILIH ALAT/TRANSAKSI (Otomatis menyesuaikan User) -->
        <div class="mb-5">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Pilih Alat yang Dikembalikan</label>
            <select name="peminjaman_id" id="peminjaman_select" required class="w-full" autocomplete="off">
                <option value="">Pilih user terlebih dahulu...</option>
            </select>
        </div>

        <hr class="my-5 border-gray-200">

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Kondisi Alat Saat Dikembalikan</label>
            <input type="text" name="kondisi_kembali" required placeholder="Contoh: Baik dan Lengkap / Ada lecet sedikit"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
        </div>

        <!-- Unit rusak berat / hilang (terisi otomatis setelah transaksi dipilih) -->
        <div id="tidak_layak_wrap" class="mb-4 hidden">
            <label class="block text-gray-700 text-sm font-semibold mb-1">Unit Tidak Layak (Rusak Berat / Hilang)</label>
            <p class="text-[11px] text-gray-500 mb-2">Unit ini tidak akan ditambahkan kembali ke stok. Biarkan 0 jika semua layak.</p>
            <div id="tidak_layak_box" class="space-y-1.5"></div>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Denda Kerusakan (Opsional - Rp)</label>
            <input type="number" name="denda_kerusakan" value="0" min="0" max="10000000" placeholder="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
            <p class="text-[11px] text-red-500 mt-1 font-semibold">*Denda keterlambatan (Rp 1.000/hari) akan dihitung otomatis oleh sistem dan ditambahkan ke total denda.</p>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('admin.pengembalian.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                Proses Pengembalian
            </button>
        </div>
    </form>
</div>

<!-- Script Tom Select -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const rawData = @json($peminjamanAktif);
        
        // Mapping data dengan aman menggunakan JavaScript murni
        const transaksiAktif = rawData.map(function(item) {
            let alatText = 'Alat tidak ditemukan';
            if (item.detail_pinjam && item.detail_pinjam.length > 0) {
                alatText = item.detail_pinjam.map(function(detail) {
                    let namaAlat = (detail.alat && detail.alat.nama_alat) ? detail.alat.nama_alat : 'Dihapus';
                    return namaAlat + ' (' + detail.jumlah + ' unit)';
                }).join(', ');
            }
            
            return {
                id: item.id,
                user_id: item.user_id,
                text: alatText + ' | Tgl Pinjam: ' + item.tgl_pinjam,
                details: (item.detail_pinjam || []).map(function(d) {
                    return {
                        id: d.id,
                        nama: (d.alat && d.alat.nama_alat) ? d.alat.nama_alat : 'Dihapus',
                        jumlah: d.jumlah
                    };
                })
            };
        });

        // Tampilkan input "unit tidak layak" untuk tiap alat pada transaksi yang dipilih
        function renderTidakLayak(peminjamanId) {
            const wrap = document.getElementById('tidak_layak_wrap');
            const box = document.getElementById('tidak_layak_box');
            box.innerHTML = '';

            const trx = transaksiAktif.find(function(t) { return t.id == peminjamanId; });
            if (!trx || trx.details.length === 0) {
                wrap.classList.add('hidden');
                return;
            }

            trx.details.forEach(function(d) {
                // Properti DOM / textContent: aman dari XSS
                const row = document.createElement('label');
                row.className = 'flex items-center justify-between gap-3 text-sm text-gray-700';
                const teks = document.createElement('span');
                teks.textContent = d.nama + ' (dipinjam ' + d.jumlah + ' unit)';
                const input = document.createElement('input');
                input.type = 'number';
                input.name = 'tidak_layak[' + d.id + ']';
                input.min = 0;
                input.max = d.jumlah;
                input.step = 1;
                input.value = 0;
                input.className = 'w-20 px-2 py-1 border border-gray-300 rounded-lg text-sm text-right focus:outline-none focus:ring-2 focus:ring-purple-500';
                row.appendChild(teks);
                row.appendChild(input);
                box.appendChild(row);
            });
            wrap.classList.remove('hidden');
        }

        // Inisialisasi TomSelect untuk Alat
        let peminjamanSelect = new TomSelect("#peminjaman_select", {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            options: [],
            create: false,
            onChange: renderTidakLayak,
        });
        peminjamanSelect.disable();

        // Inisialisasi TomSelect untuk User
        new TomSelect("#user_select", {
            create: false,
            sortField: { field: "text", direction: "asc" },
            onChange: function(userId) {
                peminjamanSelect.clear();
                peminjamanSelect.clearOptions();
                
                if (userId) {
                    const filteredTransaksi = transaksiAktif.filter(function(t) {
                        return t.user_id == userId;
                    });
                    
                    peminjamanSelect.addOptions(filteredTransaksi);
                    peminjamanSelect.enable();
                } else {
                    peminjamanSelect.disable();
                }
            }
        });
    });
</script>
@endsection