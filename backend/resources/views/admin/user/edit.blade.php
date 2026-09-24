@extends('layouts.dev')

@section('title', 'Edit User - Panel Admin')
@section('header-title', 'Edit Data Pengguna')

@section('content')
<div class="max-w-xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <!-- PERHATIKAN: Tambahan enctype="multipart/form-data" wajib untuk upload file -->
    <form action="{{ route('admin.user.update', $user->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Password Baru
                <span class="text-xs text-gray-400 font-normal">(Kosongkan jika tidak ingin mengubah password)</span></label>
            <input type="password" name="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Role / Hak Akses</label>
            <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="peminjam" {{ $user->role == 'peminjam' ? 'selected' : '' }}>Peminjam</option>
                <option value="petugas" {{ $user->role == 'petugas' ? 'selected' : '' }}>Petugas</option>
                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">No. HP</label>
            <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Alamat</label>
            <input type="text" name="alamat" value="{{ old('alamat', $user->alamat) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('alamat') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <!-- INPUT FOTO PROFIL & PREVIEW -->
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">Foto Profil</label>
            
            <!-- Menampilkan foto lama jika ada -->
            @if($user->foto_profil)
                <div class="mb-3 flex items-center gap-4 bg-gray-50 p-3 rounded-lg border border-gray-200 w-fit">
                    <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="Foto {{ $user->name }}" class="h-16 w-16 object-cover rounded-full border shadow-sm">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="hapus_foto" value="1" class="form-checkbox text-red-500 h-4 w-4 rounded border-gray-300 focus:ring-red-500">
                        <span class="ml-2 text-sm text-red-600 font-semibold hover:text-red-700">Hapus foto ini</span>
                    </label>
                </div>
            @endif

            <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/jpg,image/webp" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah foto. Format: JPG, PNG, WEBP. Maks 2MB.</p>
            @error('foto_profil') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('admin.user.index') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">Batal</a>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">Perbarui</button>
        </div>
    </form>
</div>
@endsection