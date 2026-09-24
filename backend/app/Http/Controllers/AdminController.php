<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\DetailPinjam; 
use App\Models\Pengembalian;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        // Mengambil data statistik untuk dashboard
        $totalAlat = Alat::count();
        $totalStok = Alat::sum('stok');
        $totalKategori = Kategori::count();
        $totalUser = User::count(); 
        $totalPeminjaman = Peminjaman::count();
        $peminjamanTelat = Peminjaman::with('user') 
            ->where('status', 'dipinjam') 
            ->whereDate('tgl_kembali_plan', '<', now()) 
            ->get();
        
        // Mengambil alat yang stoknya sudah menipis (3 atau kurang)
        $stokMenipis = Alat::where('stok', '<=', 3)->get();

        return view('admin.dashboard', compact('totalAlat', 'totalStok', 'totalKategori', 'totalUser', 'totalPeminjaman', 'peminjamanTelat','stokMenipis'));
    }

    // CRUD Alat: Menampilkan daftar alat
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alats = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                return $query->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama_kategori', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.alat.index', compact('alats', 'search'));
    }

    // 2. Menampilkan form tambah alat
    public function createAlat()
    {
        $kategoris = Kategori::all();
        return view('admin.alat.create', compact('kategoris'));
    }

    // 3. Menyimpan alat baru
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->all();

        // Handle Upload Gambar menggunakan Storage Facade
        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        Alat::create($data);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil ditambahkan.');
    }

    // 4. Menampilkan form edit alat
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategoris = Kategori::all();
        return view('admin.alat.edit', compact('alat', 'kategoris'));
    }

    // 5. Memperbarui data alat
    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->except(['gambar', 'hapus_gambar']);

        if ($request->has('hapus_gambar') && $request->hapus_gambar == '1') {
            if ($alat->gambar) {
                Storage::disk('public')->delete($alat->gambar);
            }
            $data['gambar'] = null;
        }

        // Handle Update Gambar menggunakan Storage Facade
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($alat->gambar) {
                Storage::disk('public')->delete($alat->gambar);
            }
            // Simpan gambar baru
            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        $alat->update($data);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil diperbarui.');
    }

    // 6. Menghapus data alat
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);

        // Hapus file gambar fisik jika ada menggunakan Storage Facade
        if ($alat->gambar) {
            Storage::disk('public')->delete($alat->gambar);
        }

        $alat->delete();

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil dihapus.');
    }

    // 1. Menampilkan daftar peminjaman
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjamans = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjaman.index', compact('peminjamans', 'search'));
    }

    // 2. Menampilkan form tambah peminjaman
    public function createPeminjaman()
    {
        $users = User::where('role', 'peminjam')->get(); 
        $alats = Alat::where('stok', '>', 0)->get();
        return view('admin.peminjaman.create', compact('users', 'alats'));
    }

    // 3. Menyimpan data peminjaman baru
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'tgl_pinjam' => 'required|date',
            'tgl_kembali_plan' => 'required|date|after_or_equal:tgl_pinjam',
            'alat_id' => 'required|array',
            'alat_id.*' => 'exists:alat,id',
            'jumlah' => 'required|array',
            'jumlah.*' => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::create([
                'user_id' => $request->user_id,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status' => 'diajukan', // Status awal
            ]);

            foreach ($request->alat_id as $index => $alatId) {
                $jumlahPinjam = $request->jumlah[$index];
                
                // Gunakan lockForUpdate walau hanya mengecek
                $alat = Alat::lockForUpdate()->findOrFail($alatId);

                if ($alat->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alat->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alatId,
                    'jumlah' => $jumlahPinjam,
                ]);
            }

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Memperbarui status peminjaman 
    public function updateStatusPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjam.alat')->findOrFail($id);

        // 1. UBAH KATA 'selesai' MENJADI 'dikembalikan' DI BARIS INI
        $request->validate([
            'status' => 'required|in:diajukan,dipinjam,dikembalikan,telat', 
        ]);

        DB::beginTransaction();
        try {
            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;

            if ($statusLama != 'dipinjam' && $statusBaru == 'dipinjam') {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                    
                    if ($alat->stok < $detail->jumlah) {
                        throw new \Exception("Stok alat {$alat->nama_alat} tidak mencukupi untuk dipinjam.");
                    }
                    $alat->decrement('stok', $detail->jumlah);
                }
            // 2. UBAH KATA 'selesai' MENJADI 'dikembalikan' DI BARIS INI JUGA
            } elseif ($statusLama == 'dipinjam' && $statusBaru == 'dikembalikan') { 
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->update(['status' => $statusBaru]);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Status peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // 5. Menghapus data peminjaman
    public function destroyPeminjaman($id)
    {
        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

            // Jika statusnya sedang dipinjam, kembalikan stok terlebih dahulu sebelum dihapus
            if ($peminjaman->status == 'dipinjam') {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                    $alat->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->delete();
            
            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    
    // Crud User (Manajemen User Admin, Petugas, Peminjam)
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.user.index', compact('users', 'search'));
    }

    public function createUser()
    {
        return view('admin.user.create');
    }

    // Menyimpan user baru ke database
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,peminjam',
            'alamat' => 'required|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // 1. Siapkan semua data array termasuk file foto
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
        ];

        // 2. Cek dan proses upload foto jika ada
        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $request->file('foto_profil')->store('profil_users', 'public');
        }

        // 3. Simpan ke database sekali saja secara utuh
        User::create($data);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

    // Memperbarui data user
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
            'alamat' => 'required|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->has('hapus_foto') && $request->hapus_foto == '1') {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $data['foto_profil'] = null;
        }

        // LOGIKA UPLOAD FOTO BARU
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama di folder sebelum ditimpa yang baru
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            // Simpan foto baru
            $data['foto_profil'] = $request->file('foto_profil')->store('profil_users', 'public');
        }

        $user->update($data);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    // Menghapus user
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        // LOGIKA HAPUS FILE FOTO AGAR MEMORI TIDAK PENUH
        if ($user->foto_profil) {
            Storage::disk('public')->delete($user->foto_profil);
        }
        $user->delete();

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }

    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategoris = Kategori::when($search, function ($query, $search) {
                return $query->where('nama_kategori', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.kategori.index', compact('kategoris', 'search'));
    }

    // 2. Menampilkan form tambah kategori
    public function createKategori()
    {
        return view('admin.kategori.create');
    }

    // 3. Menyimpan kategori baru
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    // 4. Menampilkan form edit kategori
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        return view('admin.kategori.edit', compact('kategori'));
    }

    // 5. Memperbarui kategori
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    // 6. Menghapus kategori
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Opsional: Cek apakah kategori masih dipakai oleh alat
        if ($kategori->alat()->count() > 0) {
            return redirect()->route('admin.kategori.index')
            ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
        }

        $kategori->delete();

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }

    // Memproses pengembalian alat dan mencatat denda/kondisi
    public function prosesPengembalian(Request $request, $id)
    {
        $request->validate([
            'kondisi_kembali' => 'required|string|max:255',
            'denda'           => 'nullable|numeric|min:0',
        ]);

        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        // Hanya bisa dikembalikan jika statusnya dipinjam atau telat
        if (!in_array($peminjaman->status, ['dipinjam', 'telat'])) {
            return redirect()->back()->with('error', 'Status peminjaman tidak valid untuk dikembalikan.');
        }

        DB::beginTransaction();
        try {
            // 1. Catat ke tabel pengembalian
            Pengembalian::create([
                'peminjaman_id'   => $peminjaman->id,
                'tgl_kembali'     => now()->toDateString(),
                'kondisi_kembali' => $request->kondisi_kembali,
                'denda'           => $request->denda ?? 0,
                'petugas_id'      => auth()->id(), 
            ]);

            // 2. Kembalikan stok alat (menggunakan lockForUpdate seperti standar Anda)
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::lockForUpdate()->findOrFail($detail->alat_id);
                $alat->increment('stok', $detail->jumlah);
            }

            // 3. Ubah status peminjaman menjadi selesai
            $peminjaman->update(['status' => 'dikembalikan']);

            DB::commit();
            return redirect()->route('admin.peminjaman.index')->with('success', 'Data pengembalian berhasil diproses dan stok telah ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
        }
    }

    // Menampilkan halaman Kelola Pengembalian (Riwayat)
    public function indexPengembalian(Request $request)
    {
        $search = $request->input('search');

        $pengembalians = Pengembalian::with(['peminjaman.user', 'peminjaman.detailPinjam.alat', 'petugas'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('peminjaman.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('kondisi_kembali', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pengembalian.index', compact('pengembalians', 'search'));
    }

    // 1. Fungsi Dashboard Baru
    public function dashboard()
    {
        // Karena fitur peminjaman belum dibuat, kita gunakan data master yang ada dulu
        $totalAlat = Alat::count();
        $totalStok = Alat::sum('stok');
        $totalKategori = Kategori::count();
        $stokMenipis = Alat::where('stok', '<=', 3)->get(); // Mengambil alat yang stoknya 3 ke bawah

        return view('admin.dashboard', compact('totalAlat', 'totalStok', 'totalKategori', 'stokMenipis'));
    }

    // 2. Fungsi Halaman Log Aktivitas Baru
    public function logAktivitas()
    {
        // Mengambil log terbaru beserta data user-nya
        $logs = LogAktivitas::with('user')->latest()->get();
        return view('admin.log_aktivitas.index', compact('logs'));
    }
}