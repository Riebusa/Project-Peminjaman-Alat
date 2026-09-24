@extends('layouts.dev')

@section('title', 'Kelola User - Panel Admin')
@section('header-title', 'Manajemen Pengguna Sistem')

@section('content')
    <!-- Notifikasi Sukses/Gagal -->
    @if(session('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-lg shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        
        <!-- HEADER TABEL (Dirapikan agar sejajar) -->
        <div class="p-5 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h3 class="text-lg font-bold text-gray-800">Daftar Pengguna Sistem</h3>
            
            <div class="flex items-center gap-3 w-full md:w-auto">
                <!-- Form Search -->
                <form action="{{ route('admin.user.index') }}" method="GET" class="flex w-full md:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, role..."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 text-sm font-semibold rounded-r-lg transition">
                        Cari
                    </button>
                    @if(request('search'))
                    <a href="{{ route('admin.user.index') }}"
                        class="ml-2 bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition" title="Reset Pencarian">
                        Reset
                    </a>
                    @endif
                </form>

                <!-- Tombol Tambah User -->
                <a href="{{ route('admin.user.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition whitespace-nowrap shadow-sm">
                    + Tambah User
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-200 text-gray-600 text-sm uppercase tracking-wider">
                        <!-- Kolom Nama dan Email digabung menjadi Data Pengguna -->
                        <th class="py-4 px-6 font-semibold">Data Pengguna</th>
                        <th class="py-4 px-6 font-semibold">Role / Hak Akses</th>
                        <th class="py-4 px-6 font-semibold">No. HP</th>
                        <th class="py-4 px-6 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        
                        <!-- KOLOM DATA PENGGUNA (FOTO, NAMA, EMAIL) -->
                        <td class="py-3 px-6">
                            <div class="flex items-center gap-3">
                                <!-- Logika Foto / Inisial Nama -->
                                @if($user->foto_profil)
                                    <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="Avatar" class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm flex-shrink-0">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                
                                <div>
                                    <p class="font-bold text-gray-900 text-sm">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-6 align-middle">
                            <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                                @if($user->role == 'admin') bg-purple-100 text-purple-800
                                @elseif($user->role == 'petugas') bg-blue-100 text-blue-800
                                @else bg-emerald-100 text-emerald-800 @endif">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="py-3 px-6 align-middle text-gray-600">{{ $user->no_hp ?? '-' }}</td>
                        <td class="py-3 px-6 align-middle text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- Tombol Edit -->
                                <a href="{{ route('admin.user.edit', $user->id) }}"
                                    class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition shadow-sm">
                                    Edit
                                </a>

                                <!-- Tombol Hapus -->
                                <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition shadow-sm">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <!-- Colspan diubah menjadi 4 karena Nama dan Email sudah digabung -->
                        <td colspan="4" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <p>Belum ada data pengguna.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-gray-200 bg-gray-50">
            {{ $users->links() }}   
        </div>
        @endif
    </div>
@endsection