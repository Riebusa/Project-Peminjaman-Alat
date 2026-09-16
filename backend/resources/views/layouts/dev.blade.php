<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Title juga dibikin dinamis berdasarkan halaman -->
    <title>@yield('title', 'Aplikasi Inventaris')</title>
    <!-- Memuat Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased">
    
    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR -->
        <aside class="w-64 bg-gray-900 text-white flex flex-col hidden md:flex">
            <!-- JUDUL PANEL (Dinamis sesuai role) -->
            <div class="p-5 text-xl font-bold tracking-wider border-b border-gray-800 uppercase">
                PANEL {{ auth()->user()->role }}
            </div>
            
            <nav class="flex-1 p-4 space-y-2">
                
                <!-- ========================================== -->
                <!-- MENU KHUSUS ADMIN -->
                <!-- ========================================== -->
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Dashboard</a>

                    <a href="{{ route('admin.user.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.user*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola User</a>

                    <a href="{{ route('admin.kategori.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.kategori*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola Kategori</a>

                    <a href="{{ route('admin.alat.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.alat*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola Alat</a>

                    <a href="{{ route('admin.peminjaman.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.peminjaman*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola Peminjaman</a>

                    <a href="{{ route('admin.pengembalian.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.pengembalian*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola Pengembalian</a>

                    <a href="{{ route('admin.log.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('admin.log.index*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Log Aktivitas</a>

                <!-- ========================================== -->
                <!-- MENU KHUSUS PETUGAS -->
                <!-- ========================================== -->
                @elseif(auth()->user()->role === 'petugas')
                    
                    <a href="{{ route('petugas.dashboard') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('petugas.dashboard') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Dashboard</a>

                    <!-- Garis pembatas kecil untuk estetika -->
                    <div class="pt-4 pb-2 px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        Tugas Operasional
                    </div>

                    <a href="{{ route('petugas.peminjaman.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('petugas.peminjaman*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Persetujuan Peminjaman</a>

                    <a href="{{ route('petugas.pengembalian.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('petugas.pengembalian*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Kelola Pengembalian</a>

                    <a href="{{ route('petugas.laporan.index') }}" class="block px-4 py-2 rounded-lg transition 
                    {{ request()->routeIs('petugas.laporan*') ? 'bg-gray-800 text-white font-medium shadow' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                    Cetak Laporan</a>
                    
                @endif
            </nav>
            
            <div class="p-4 border-t border-gray-800 text-sm text-gray-400">
                Logged in as: <span class="text-white font-semibold">{{ auth()->user()->name }}</span>
            </div>
        </aside>

        <!-- Main Content Container -->
        <div class="flex-1 flex flex-col overflow-y-auto">
            <!-- Navbar Atas -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10">
                <div class="text-lg font-semibold text-gray-800">
                    @yield('header-title', 'Dashboard')
                </div>
                <div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Konten Utama Halaman -->
            <main class="flex-1 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>