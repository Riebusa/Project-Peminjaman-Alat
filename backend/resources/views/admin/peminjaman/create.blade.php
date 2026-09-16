@extends('layouts.dev')

@section('title', 'Tambah Peminjaman - Panel Admin')
@section('header-title', 'Form Tambah Transaksi Peminjaman')

@section('content')
<!-- CSS Tom Select -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    /* Styling agar desain dropdown mirip dengan form bawaan Anda */
    .ts-wrapper .ts-control {
        border: 1px solid #d1d5db !important; 
        padding: 0.5rem 0.75rem !important;   
        border-radius: 0.5rem !important;     
        background-color: #ffffff !important;
        box-shadow: none !important;
        font-size: 0.875rem !important; /* Menyamakan text-sm */
        min-height: 38px !important; 
        display: flex;
        align-items: center;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #3b82f6 !important;     
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important; 
        outline: none !important;
    }
    /* Memastikan dropdown list-nya merespons lebar parent */
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

    <form action="{{ route('admin.peminjaman.store') }}" method="POST">
        @csrf

        <!-- PILIH USER -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Pilih Peminjam (User)</label>
            <!-- Hapus padding bawaan, karena sudah diatur oleh CSS Tom Select -->
            <select name="user_id" id="user_select" required class="w-full" autocomplete="off">
                <option value="">Ketik untuk mencari user (nama/email)...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Tanggal Pinjam</label>
                <input type="date" name="tgl_pinjam" value="{{ old('tgl_pinjam', date('Y-m-d')) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Rencana Tanggal Kembali</label>
                <input type="date" name="tgl_kembali_plan" value="{{ old('tgl_kembali_plan', date('Y-m-d', strtotime('+3 days'))) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <!-- DAFTAR ALAT -->
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Daftar Alat yang Dipinjam</label>
            <div id="alat-container" class="space-y-3">
                <div class="flex items-center gap-2 alat-row w-full">
                    <div class="flex-1">
                        <!-- Tambahkan class 'alat_select' untuk identifikasi JS -->
                        <select name="alat_id[]" required class="w-full alat_select" autocomplete="off">
                            <option value="">Ketik untuk mencari alat...</option>
                            @foreach($alats as $alat)
                                <option value="{{ $alat->id }}">{{ $alat->nama_alat }} (Stok: {{ $alat->stok }})</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="number" name="jumlah[]" value="1" min="1" placeholder="Qty"
                        class="w-20 px-3 py-2 h-[38px] border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" onclick="removeRow(this)" class="bg-red-500 text-white px-3 py-2 h-[38px] rounded-lg text-sm hover:bg-red-600 transition flex items-center justify-center font-bold">X</button>
                </div>
            </div>
            <button type="button" onclick="addRow()" class="mt-3 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                + Tambah Alat Lain
            </button>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('admin.peminjaman.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Simpan Peminjaman</button>
        </div>
    </form>
</div>

<!-- Script Tom Select & Logika Dinamis Dinamis -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Inisialisasi Tom Select untuk User
        new TomSelect("#user_select", {
            create: false,
            sortField: { field: "text", direction: "asc" }
        });

        // 2. Inisialisasi Tom Select untuk Alat baris pertama (yang langsung muncul)
        initTomSelect('.alat_select');
    });

    // Fungsi bantuan untuk mengaktifkan Tom Select
    function initTomSelect(selector) {
        document.querySelectorAll(selector).forEach(function(el) {
            // Cek apakah elemen sudah punya instance Tom Select, jika belum buat baru
            if (!el.tomselect) {
                new TomSelect(el, {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
        });
    }

    // Fungsi Tambah Baris
    function addRow() {
        const container = document.getElementById('alat-container');
        const firstRow = container.querySelector('.alat-row');
        
        // Gandakan elemen baris pertama
        const newRow = firstRow.cloneNode(true);
        
        // Hapus elemen div bentukan Tom Select yang ter-clone
        const tsControl = newRow.querySelector('.ts-wrapper');
        if (tsControl) tsControl.remove(); 
        
        // Ambil tag select asli (yang disembunyikan oleh Tom Select), hapus atribut 'hidden' dan value-nya
        const selectEl = newRow.querySelector('select');
        selectEl.classList.remove('tomselected', 'ts-hidden-accessible');
        selectEl.style.display = 'block';
        selectEl.removeAttribute('hidden');
        selectEl.removeAttribute('tabindex');
        selectEl.value = ''; 
        
        // Reset nilai input qty
        newRow.querySelector('input').value = '1';
        
        // Masukkan ke dalam container
        container.appendChild(newRow);
        
        // Aktifkan kembali fungsi Tom Select pada tag select yang baru saja direkayasa
        new TomSelect(selectEl, {
            create: false,
            sortField: { field: "text", direction: "asc" }
        });
    }

    // Fungsi Hapus Baris
    function removeRow(button) {
        const rows = document.querySelectorAll('.alat-row');
        if (rows.length > 1) {
            button.closest('.alat-row').remove();
        } else {
            alert('Minimal harus ada 1 alat yang dipilih.');
        }
    }
</script>
@endsection