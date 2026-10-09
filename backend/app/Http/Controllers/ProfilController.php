<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        return view('profil.index', [
            'user'      => $user,
            // Peminjam memakai layout navbar atas, admin & petugas memakai sidebar
            'layout'    => $user->role === 'peminjam' ? 'layouts.peminjam' : 'layouts.dev',
            'ringkasan' => $this->ringkasan($user),
            'riwayat'   => LogAktivitas::where('user_id', $user->id)
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
            // Login terakhir sebelum sesi ini (login yang sekarang adalah yang terbaru)
            'loginSebelumnya' => LogAktivitas::where('user_id', $user->id)
                ->where('aktivitas', 'like', 'Masuk (login)%')
                ->orderByDesc('id')
                ->offset(1)
                ->limit(1)
                ->first(),
        ]);
    }

    // Ubah data diri: nama, no. HP, alamat, foto
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'        => 'required|string|max:255',
            'no_hp'       => 'nullable|string|max:20',
            'alamat'      => 'required|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->only(['name', 'no_hp', 'alamat']);

        if ($request->boolean('hapus_foto') && $user->foto_profil) {
            Storage::disk('public')->delete($user->foto_profil);
            $data['foto_profil'] = null;
        }

        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil) {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $data['foto_profil'] = $request->file('foto_profil')->store('profil_users', 'public');
        }

        $user->update($data);

        return redirect()->route('profil.show')->with('success', 'Profil berhasil diperbarui.');
    }

    // Ganti email: wajib konfirmasi password saat ini
    public function updateEmail(Request $request)
    {
        $user = auth()->user();

        $request->validateWithBag('updateEmail', [
            'email'               => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password_konfirmasi' => ['required', 'current_password'],
        ]);

        $user->update(['email' => $request->email]);

        return redirect()->route('profil.show')->with('success', 'Email berhasil diperbarui.');
    }

    // Ganti password: wajib password lama
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validateWithBag('updatePassword', [
            'password_lama' => ['required', 'current_password'],
            'password'      => ['required', 'string', 'min:6', 'confirmed', 'different:password_lama'],
        ]);

        // Di-hash otomatis oleh cast 'hashed' pada model User
        $user->update(['password' => $request->password]);

        return redirect()->route('profil.show')->with('success', 'Password berhasil diganti.');
    }

    // Kartu ringkasan yang berbeda untuk tiap role
    private function ringkasan(User $user): array
    {
        if ($user->role === 'peminjam') {
            $totalDenda = Pengembalian::whereHas('peminjaman', fn ($q) => $q->where('user_id', $user->id))
                ->sum('denda');

            return [
                ['label' => 'Total Peminjaman', 'nilai' => Peminjaman::where('user_id', $user->id)->count()],
                ['label' => 'Sedang Dipinjam', 'nilai' => Peminjaman::where('user_id', $user->id)
                    ->whereIn('status', ['dipinjam', 'telat', 'menunggu_pengembalian'])->count()],
                ['label' => 'Terlambat', 'nilai' => Peminjaman::where('user_id', $user->id)
                    ->whereIn('status', ['dipinjam', 'telat'])
                    ->whereDate('tgl_kembali_plan', '<', today())->count()],
                ['label' => 'Total Denda', 'nilai' => 'Rp ' . number_format($totalDenda, 0, ',', '.')],
            ];
        }

        if ($user->role === 'petugas') {
            $dendaTercatat = Pengembalian::where('petugas_id', $user->id)->sum('denda');

            return [
                ['label' => 'Peminjaman Disetujui', 'nilai' => LogAktivitas::where('user_id', $user->id)
                    ->where('aktivitas', 'like', 'MENYETUJUI peminjaman%')->count()],
                ['label' => 'Pengembalian Diproses', 'nilai' => Pengembalian::where('petugas_id', $user->id)->count()],
                ['label' => 'Denda Tercatat', 'nilai' => 'Rp ' . number_format($dendaTercatat, 0, ',', '.')],
            ];
        }

        // Admin
        return [
            ['label' => 'Total User', 'nilai' => User::count()],
            ['label' => 'User Aktif', 'nilai' => User::where('is_active', true)->count()],
            ['label' => 'Total Alat', 'nilai' => Alat::count()],
            ['label' => 'Aksi Tercatat', 'nilai' => LogAktivitas::where('user_id', $user->id)->count()],
        ];
    }
}