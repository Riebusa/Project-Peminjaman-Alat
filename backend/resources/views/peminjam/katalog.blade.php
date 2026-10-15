@extends('layouts.peminjam')

@section('title', 'Katalog Alat - Peminjam')
@section('header-title', 'Katalog Peminjaman Alat')

@section('content')
    <!-- Notifikasi Sukses / Error -->
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
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg shadow-sm text-sm">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Peringatan: pengajuan baru diblokir -->
    @if($alasanBlokir)
        <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-lg shadow-sm text-sm flex items-start justify-between gap-4">
            <p>{{ $alasanBlokir }}</p>
            <a href="{{ route('peminjam.riwayat') }}" class="whitespace-nowrap font-semibold underline">Buka Riwayat</a>
        </div>
    @endif

    <!-- Baris Pencarian & Filter -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('peminjam.katalog') }}" method="GET" class="flex flex-col md:flex-row gap-3 md:items-center">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama alat..."
                class="w-full md:w-80 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

            <select name="kategori" class="w-full md:w-56 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">Semua Kategori</option>
                @foreach($kategoris as $kategori)
                    <option value="{{ $kategori->id }}" @selected((string) request('kategori') === (string) $kategori->id)>{{ $kategori->nama_kategori }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 text-sm font-semibold rounded-lg transition">
                    Cari
                </button>
                @if(request('search') || request('kategori'))
                    <a href="{{ route('peminjam.katalog') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-3 py-2 text-sm rounded-lg flex items-center transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Grid Katalog Alat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($alats as $alat)
        @php
            $habis = $alat->stok < 1;
            $nonaktif = $habis || $alasanBlokir;
        @endphp
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between transition-transform hover:-translate-y-1 duration-200">
            <div class="{{ $habis ? 'opacity-60' : '' }}">
                <!-- Gambar Alat -->
                @if($alat->gambar)
                    <img src="{{ asset('storage/' . $alat->gambar) }}" alt="{{ $alat->nama_alat }}" class="w-full h-48 object-cover bg-gray-100">
                @else
                    <div class="w-full h-48 bg-gray-100 flex items-center justify-center text-gray-400 text-xs font-semibold uppercase">Tidak Ada Gambar</div>
                @endif

                <div class="p-4">
                    <span class="px-2 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 rounded-full border border-blue-100">
                        {{ $alat->kategori->nama_kategori ?? 'Umum' }}
                    </span>
                    <h4 class="font-bold text-gray-900 text-base mt-2">{{ $alat->nama_alat }}</h4>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $alat->deskripsi ?? 'Tidak ada deskripsi tersedia untuk alat ini.' }}</p>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                        <span class="text-gray-600 font-medium">Stok Tersedia:</span>
                        @if($habis)
                            <span class="font-bold text-red-500">Stok habis</span>
                        @else
                            <span class="font-bold text-emerald-600">{{ $alat->stok }} Unit</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Tambah ke Keranjang -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="mb-3">
                    <label for="qty-{{ $alat->id }}" class="block text-xs font-semibold text-gray-600 mb-1">Jml Pinjam:</label>
                    <input type="number" id="qty-{{ $alat->id }}" value="1" min="1" max="{{ max($alat->stok, 1) }}"
                        @disabled($nonaktif)
                        class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white disabled:bg-gray-100 disabled:text-gray-400">
                </div>

                <button type="button" data-tambah
                    data-id="{{ $alat->id }}" data-nama="{{ $alat->nama_alat }}" data-stok="{{ $alat->stok }}"
                    @disabled($nonaktif)
                    class="w-full text-white text-sm font-semibold py-2 rounded-md transition shadow-sm flex justify-center items-center gap-2
                    {{ $nonaktif ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' }}">
                    @if($habis)
                        Stok Habis
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Tambah ke Keranjang
                    @endif
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 flex flex-col items-center justify-center text-gray-500 bg-white rounded-lg border border-gray-200 shadow-sm">
            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
            <p class="text-lg font-medium text-gray-600">Belum ada alat yang tersedia saat ini.</p>
            <p class="text-sm mt-1">Coba gunakan kata kunci atau kategori yang lain.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $alats->links() }}
    </div>

    <!-- ================= KERANJANG ================= -->

    <!-- Tombol keranjang melayang -->
    <button type="button" id="btn-keranjang"
        class="fixed bottom-6 right-6 z-40 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm pl-4 pr-5 py-3 rounded-full shadow-lg flex items-center gap-2 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        Keranjang
        <span id="badge-keranjang" class="hidden bg-white text-blue-700 text-xs font-bold rounded-full px-2 py-0.5">0</span>
    </button>

    <!-- Pemberitahuan singkat -->
    <div id="toast-keranjang" class="hidden fixed bottom-24 right-6 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg max-w-xs"></div>

    <!-- Panel keranjang -->
    <div id="panel-keranjang" class="hidden fixed inset-0 z-50">
        <div id="overlay-keranjang" class="absolute inset-0 bg-gray-900/50"></div>

        <aside class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl flex flex-col">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Keranjang Peminjaman</h3>
                <button type="button" id="tutup-keranjang" class="text-gray-400 hover:text-gray-700 text-2xl leading-none" aria-label="Tutup">&times;</button>
            </div>

            <div id="daftar-keranjang" class="flex-1 overflow-y-auto p-4 space-y-3"></div>

            <form id="form-ajukan" action="{{ route('peminjam.ajukan') }}" method="POST" class="border-t border-gray-200 p-5 bg-gray-50">
                @csrf
                <!-- Input alat_id[] dan jumlah[] dibuat oleh JavaScript dari isi keranjang -->
                <div id="input-tersembunyi"></div>

                @if($alasanBlokir)
                    <div class="mb-3 bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-lg text-xs">
                        {{ $alasanBlokir }}
                    </div>
                @endif

                <label for="tgl-kembali" class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Kembali</label>
                <input type="date" id="tgl-kembali" name="tgl_kembali_plan" required
                    value="{{ old('tgl_kembali_plan') }}"
                    min="{{ today()->toDateString() }}" max="{{ today()->addDays($maksHari)->toDateString() }}"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <p class="text-xs text-gray-500 mt-1 mb-4">
                    Dipinjam mulai hari ini ({{ now()->translatedFormat('d M Y') }}). Maksimal {{ $maksHari }} hari,
                    dan maksimal {{ $maksAlat }} jenis alat per pengajuan.
                </p>

                <button type="submit" id="btn-ajukan"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-2.5 rounded-lg transition shadow-sm">
                    Ajukan Peminjaman
                </button>
            </form>
        </aside>
    </div>

    <script>
        (function () {
            const KUNCI = 'keranjang_{{ auth()->id() }}';
            const MAKS_ALAT = {{ $maksAlat }};
            const DIBLOKIR = @json((bool) $alasanBlokir);
            const BUKA_OTOMATIS = @json($errors->any() || session()->has('error'));

            const panel = document.getElementById('panel-keranjang');
            const daftar = document.getElementById('daftar-keranjang');
            const inputTersembunyi = document.getElementById('input-tersembunyi');
            const badge = document.getElementById('badge-keranjang');
            const btnAjukan = document.getElementById('btn-ajukan');
            const formAjukan = document.getElementById('form-ajukan');
            const toast = document.getElementById('toast-keranjang');
            let timerToast = null;

            // ---------- Penyimpanan di browser (per akun) ----------
            function baca() {
                try {
                    const data = JSON.parse(localStorage.getItem(KUNCI));
                    return Array.isArray(data) ? data : [];
                } catch (e) {
                    return [];
                }
            }

            function simpan(items) {
                try {
                    localStorage.setItem(KUNCI, JSON.stringify(items));
                } catch (e) { /* abaikan jika penyimpanan penuh atau diblokir */ }
            }

            function tampilToast(pesan) {
                toast.textContent = pesan;
                toast.classList.remove('hidden');
                clearTimeout(timerToast);
                timerToast = setTimeout(function () { toast.classList.add('hidden'); }, 2200);
            }

            // ---------- Operasi keranjang ----------
            function tambah(id, nama, stok, jumlah) {
                const items = baca();
                const ada = items.find(function (i) { return i.id === id; });

                if (ada) {
                    ada.stok = stok;
                    ada.jumlah = Math.min(ada.jumlah + jumlah, stok);
                } else {
                    if (items.length >= MAKS_ALAT) {
                        tampilToast('Maksimal ' + MAKS_ALAT + ' jenis alat dalam satu pengajuan.');
                        return;
                    }
                    items.push({ id: id, nama: nama, stok: stok, jumlah: Math.min(jumlah, stok) });
                }

                simpan(items);
                render();
                tampilToast(nama + ' ditambahkan ke keranjang.');
            }

            function ubahJumlah(id, selisih) {
                const items = baca();
                const item = items.find(function (i) { return i.id === id; });
                if (!item) return;

                item.jumlah = Math.max(1, Math.min(item.jumlah + selisih, item.stok));
                simpan(items);
                render();
            }

            function hapus(id) {
                simpan(baca().filter(function (i) { return i.id !== id; }));
                render();
            }

            // ---------- Tampilan ----------
            function buatTombol(teks, label, aksi) {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = teks;
                b.setAttribute('aria-label', label);
                b.className = 'w-7 h-7 rounded border border-gray-300 text-gray-700 hover:bg-gray-100 font-bold leading-none';
                b.addEventListener('click', aksi);
                return b;
            }

            function render() {
                const items = baca();

                badge.textContent = items.length;
                badge.classList.toggle('hidden', items.length === 0);

                daftar.innerHTML = '';
                inputTersembunyi.innerHTML = '';

                if (items.length === 0) {
                    const kosong = document.createElement('div');
                    kosong.className = 'py-16 text-center text-gray-500 text-sm';
                    kosong.textContent = 'Keranjang masih kosong. Pilih alat dari katalog lalu klik "Tambah ke Keranjang".';
                    daftar.appendChild(kosong);
                }

                items.forEach(function (item) {
                    const baris = document.createElement('div');
                    baris.className = 'border border-gray-200 rounded-lg p-3 flex items-center justify-between gap-3';

                    const info = document.createElement('div');
                    info.className = 'min-w-0';
                    const nama = document.createElement('p');
                    nama.className = 'text-sm font-semibold text-gray-900 truncate';
                    nama.textContent = item.nama;
                    const stok = document.createElement('p');
                    stok.className = 'text-xs text-gray-500';
                    stok.textContent = 'Stok tersedia: ' + item.stok;
                    info.appendChild(nama);
                    info.appendChild(stok);

                    const kontrol = document.createElement('div');
                    kontrol.className = 'flex items-center gap-2 shrink-0';
                    const angka = document.createElement('span');
                    angka.className = 'w-6 text-center text-sm font-semibold';
                    angka.textContent = item.jumlah;
                    kontrol.appendChild(buatTombol('-', 'Kurangi jumlah', function () { ubahJumlah(item.id, -1); }));
                    kontrol.appendChild(angka);
                    kontrol.appendChild(buatTombol('+', 'Tambah jumlah', function () { ubahJumlah(item.id, 1); }));

                    const btnHapus = document.createElement('button');
                    btnHapus.type = 'button';
                    btnHapus.textContent = 'Hapus';
                    btnHapus.className = 'ml-1 text-xs font-semibold text-red-600 hover:text-red-800';
                    btnHapus.addEventListener('click', function () { hapus(item.id); });
                    kontrol.appendChild(btnHapus);

                    baris.appendChild(info);
                    baris.appendChild(kontrol);
                    daftar.appendChild(baris);

                    // Input tersembunyi yang dikirim ke server
                    const inAlat = document.createElement('input');
                    inAlat.type = 'hidden';
                    inAlat.name = 'alat_id[]';
                    inAlat.value = item.id;
                    const inJumlah = document.createElement('input');
                    inJumlah.type = 'hidden';
                    inJumlah.name = 'jumlah[]';
                    inJumlah.value = item.jumlah;
                    inputTersembunyi.appendChild(inAlat);
                    inputTersembunyi.appendChild(inJumlah);
                });

                btnAjukan.disabled = items.length === 0 || DIBLOKIR;
            }

            function buka() { panel.classList.remove('hidden'); }
            function tutup() { panel.classList.add('hidden'); }

            // ---------- Pemasangan event ----------
            document.querySelectorAll('[data-tambah]').forEach(function (tombol) {
                tombol.addEventListener('click', function () {
                    const id = parseInt(tombol.dataset.id, 10);
                    const stok = parseInt(tombol.dataset.stok, 10);
                    const input = document.getElementById('qty-' + id);
                    let jumlah = parseInt(input.value, 10);
                    if (isNaN(jumlah) || jumlah < 1) jumlah = 1;
                    if (jumlah > stok) jumlah = stok;

                    tambah(id, tombol.dataset.nama, stok, jumlah);
                });
            });

            document.getElementById('btn-keranjang').addEventListener('click', buka);
            document.getElementById('tutup-keranjang').addEventListener('click', tutup);
            document.getElementById('overlay-keranjang').addEventListener('click', tutup);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });

            formAjukan.addEventListener('submit', function (e) {
                if (baca().length === 0 || DIBLOKIR) {
                    e.preventDefault();
                    return;
                }
                // Cegah klik ganda
                btnAjukan.disabled = true;
            });

            // ---------- Mulai ----------
            render();
            if (BUKA_OTOMATIS && baca().length > 0) {
                buka();
            }
        })();
    </script>
@endsection