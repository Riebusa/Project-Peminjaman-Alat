@extends($layout)

@section('title', 'Profil Saya')
@section('header-title', 'Profil Saya')

@section('content')
@php
    $warnaRole = match($user->role) {
        'admin' => 'bg-purple-100 text-purple-800',
        'petugas' => 'bg-blue-100 text-blue-800',
        default => 'bg-emerald-100 text-emerald-800',
    };
    $inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm';
@endphp

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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ================= KOLOM KIRI ================= -->
    <div class="lg:col-span-1 space-y-6">

        <!-- Kartu Identitas & Info Akun (hanya baca) -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 text-center">
            @if($user->foto_profil)
                <img src="{{ asset('storage/' . $user->foto_profil) }}" alt="Foto Profil"
                     class="h-24 w-24 rounded-full object-cover border border-gray-200 shadow-sm mx-auto">
            @else
                <div class="h-24 w-24 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-3xl shadow-sm mx-auto">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif

            <h3 class="mt-4 text-lg font-bold text-gray-900">{{ $user->name }}</h3>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>

            <div class="mt-3 flex items-center justify-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $warnaRole }}">{{ ucfirst($user->role) }}</span>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <dl class="mt-5 pt-5 border-t border-gray-100 text-left text-sm space-y-2">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Bergabung</dt>
                    <dd class="font-medium text-gray-800">{{ $user->created_at->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Login sebelumnya</dt>
                    <dd class="font-medium text-gray-800 text-right">
                        {{ $loginSebelumnya ? $loginSebelumnya->created_at->translatedFormat('d M Y, H:i') : '-' }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Ringkasan per Role -->
        <div class="grid grid-cols-2 gap-3">
            @foreach($ringkasan as $item)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                    <p class="text-[11px] text-gray-500 uppercase tracking-wider font-medium">{{ $item['label'] }}</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ $item['nilai'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ================= KOLOM KANAN ================= -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Form Data Diri -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100">Data Diri</h3>

            <form action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $inputClass }}" required>
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">No. HP</label>
                        <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}"
                               inputmode="numeric" pattern="[0-9]{8,15}" maxlength="15"
                               class="{{ $inputClass }}">
                        @error('no_hp') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat</label>
                    <input type="text" name="alamat" value="{{ old('alamat', $user->alamat) }}" class="{{ $inputClass }}" required>
                    @error('alamat') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Foto Profil</label>
                    <input type="file" name="foto_profil" accept="image/png,image/jpeg,image/webp"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                    @error('foto_profil') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    @if($user->foto_profil)
                        <label class="inline-flex items-center gap-2 mt-3 text-sm text-gray-700">
                            <input type="checkbox" name="hapus_foto" value="1" class="rounded border-gray-300">
                            Hapus foto profil saat ini
                        </label>
                    @endif
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Form Ganti Email -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-800 mb-1">Ganti Email</h3>
            <p class="text-xs text-gray-500 mb-4 pb-3 border-b border-gray-100">Email dipakai untuk login. Perubahan perlu konfirmasi password saat ini.</p>

            <form action="{{ route('profil.email') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email Baru</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $inputClass }}" required>
                        @if($errors->updateEmail->has('email'))
                            <p class="text-xs text-red-600 mt-1">{{ $errors->updateEmail->first('email') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Saat Ini</label>
                        <input type="password" name="password_konfirmasi" class="{{ $inputClass }}" required autocomplete="current-password">
                        @if($errors->updateEmail->has('password_konfirmasi'))
                            <p class="text-xs text-red-600 mt-1">{{ $errors->updateEmail->first('password_konfirmasi') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition shadow-sm">
                        Ganti Email
                    </button>
                </div>
            </form>
        </div>

        <!-- Form Ganti Password -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-800 mb-1">Ganti Password</h3>
            <p class="text-xs text-gray-500 mb-4 pb-3 border-b border-gray-100">Password baru minimal 6 karakter dan harus berbeda dari password lama.</p>

            <form action="{{ route('profil.password') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password Lama</label>
                    <input type="password" name="password_lama" class="{{ $inputClass }}" required autocomplete="current-password">
                    @if($errors->updatePassword->has('password_lama'))
                        <p class="text-xs text-red-600 mt-1">{{ $errors->updatePassword->first('password_lama') }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="password" class="{{ $inputClass }}" required autocomplete="new-password">
                        @if($errors->updatePassword->has('password'))
                            <p class="text-xs text-red-600 mt-1">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="{{ $inputClass }}" required autocomplete="new-password">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold px-5 py-2 rounded-lg transition shadow-sm">
                        Ganti Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= RIWAYAT AKTIVITAS PRIBADI ================= -->
<div class="mt-6 bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-base font-bold text-gray-800 mb-1">Aktivitas Terakhir Anda</h3>
    <p class="text-xs text-gray-500 mb-4 pb-3 border-b border-gray-100">10 aktivitas terbaru dari akun ini. Kalau ada yang bukan Anda lakukan, segera ganti password.</p>

    <ul class="divide-y divide-gray-100">
        @forelse($riwayat as $log)
            <li class="flex items-start justify-between gap-4 py-2.5 text-sm">
                <span class="text-gray-700">{{ \Illuminate\Support\Str::before($log->aktivitas, '. Detail: ') }}</span>
                <span class="text-xs text-gray-500 whitespace-nowrap">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
            </li>
        @empty
            <li class="py-6 text-center text-sm text-gray-500">Belum ada aktivitas tercatat.</li>
        @endforelse
    </ul>
</div>
@endsection