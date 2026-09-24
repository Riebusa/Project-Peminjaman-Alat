<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Peminjaman')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    
    <!-- TOP NAVIGATION BAR (NAVBAR) -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Kiri: Logo & Menu -->
                <div class="flex">
                    <!-- Logo APlikasi -->
                    <div class="flex-shrink-0 flex items-center font-black text-xl text-blue-600 tracking-tight">
                        SI<span class="text-gray-800">PINJAM</span>
                    </div>
                    <!-- Menu Link -->
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-6">
                        <!-- Gunakan Request::routeIs untuk membuat efek menu aktif/menyala -->
                        <a href="{{ route('peminjam.katalog') }}" 
                           class="{{ request()->routeIs('peminjam.katalog') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition">
                            Katalog Alat
                        </a>
                        <a href="{{ route('peminjam.riwayat') }}" 
                           class="{{ request()->routeIs('peminjam.riwayat') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-semibold transition">
                            Riwayat Peminjaman
                        </a>
                    </div>
                </div>
                
                <!-- Kanan: Profil & Logout -->
                <div class="flex items-center gap-4">
                    <!-- Tampilkan Foto Profil Peminjam jika ada (Bisa pakai Inisial jika tidak) -->
                    <div class="hidden sm:flex items-center gap-2">
                        @if(auth()->user()->foto_profil)
                            <img src="{{ asset('storage/' . auth()->user()->foto_profil) }}" alt="Avatar" class="h-8 w-8 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-sm font-semibold text-gray-700">{{ auth()->user()->name ?? 'Peminjam' }}</span>
                    </div>

                    <!-- Tombol Logout -->
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-sm font-bold transition">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- HEADER HALAMAN (Opsional, untuk Judul) -->
    <header class="bg-white shadow-sm mb-6">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">@yield('header-title')</h1>
        </div>
    </header>

    <!-- KONTEN UTAMA (Yang akan diisi oleh katalog.blade.php & riwayat.blade.php) -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        @yield('content')
    </main>
    
</body>
</html>