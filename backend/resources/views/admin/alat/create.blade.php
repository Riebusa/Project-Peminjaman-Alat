@extends('layouts.dev')

@section('title', 'Tambah Alat - Panel Admin')
@section('header-title', 'Tambah Alat Baru')

@section('content')
<!-- Tambahkan ini untuk memuat gaya CSS dropdown modern -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

<!-- KODE CSS UNTUK MENYAMAKAN STYLE TOM SELECT DENGAN TAILWIND LAMA -->
<style>
    /* Menyamakan border, padding, dan rounded */
    .ts-wrapper .ts-control {
        border: 1px solid #d1d5db !important; /* border-gray-300 */
        padding: 0.5rem 0.75rem !important;   /* py-2 px-3 */
        border-radius: 0.5rem !important;     /* rounded-lg */
        background-color: #ffffff !important;
        box-shadow: none !important;
        font-size: 1rem !important;
        line-height: 1.5 !important;
        min-height: 42px !important; /* Menyamakan tinggi persis dengan input lain */
        display: flex;
        align-items: center;
    }
    
    /* Menyamakan efek klik (focus:ring-blue-500) */
    .ts-wrapper.focus .ts-control {
        border-color: #3b82f6 !important;     /* border-blue-500 */
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5) !important; /* ring-2 ring-blue-500 */
        outline: none !important;
    }

    /* Merapikan text input di dalamnya */
    .ts-control > input {
        font-size: 1rem !important;
        padding: 0 !important;
        margin: 0 !important;
        line-height: 1.5 !important;
    }
</style>

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <form action="{{ route('admin.alat.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Nama Alat</label>
            <input type="text" name="nama_alat" value="{{ old('nama_alat') }}" required placeholder="Contoh: Multimeter Digital"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('nama_alat') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Kategori</label>
            <!-- <select name="kategori_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">— Pilih Kategori —</option>
                @foreach($kategoris as $kategori)
                    <option value="{{ $kategori->id }}" {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select> -->
            <select name="kategori_id" id="kategori_id" class="w-full" required autocomplete="off">
                <option value="">Ketik untuk mencari kategori...</option>
                @foreach($kategoris as $kat)
                    <option value="{{ $kat->id }}" {{ old('kategori_id') == $kat->id ? 'selected' : '' }}>
                        {{ $kat->nama_kategori }}
                    </option>
                @endforeach
            </select>
            @error('kategori_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Stok</label>
                <input type="number" name="stok" value="{{ old('stok', 1) }}" min="0" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('stok') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">Status Kondisi</label>
                <input type="text" name="status_kondisi" value="{{ old('status_kondisi') }}" required placeholder="Contoh: Baik / Rusak Ringan"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('status_kondisi') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Deskripsi (Opsional)</label>
            <textarea name="deskripsi" rows="3" placeholder="Keterangan tambahan tentang alat..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('deskripsi') }}</textarea>
            @error('deskripsi') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Gambar Alat (Opsional)</label>
            <input type="file" name="gambar" accept="image/*"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @error('gambar') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('admin.alat.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Simpan</button>
        </div>
    </form>
</div>

<!-- Script untuk mengaktifkan fitur pencarian (Tom Select) -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect("#kategori_id", {
            create: false,           // Matikan fitur tambah kategori dari dropdown (biar khusus admin/kategori)
            sortField: {
                field: "text",
                direction: "asc"     // Urutkan abjad A-Z otomatis
            },
            placeholder: "Ketik untuk mencari kategori...",
        });
    });
</script>
@endsection