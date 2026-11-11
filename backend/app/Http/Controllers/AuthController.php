<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\LogAktivitas;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Akun dinonaktifkan: beri pesan jelas (hanya jika password benar)
        $akun = User::where('email', $credentials['email'])->first();
        if ($akun && !$akun->is_active && Hash::check($credentials['password'], $akun->password)) {
            return back()->withErrors([
                'email' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials + ['is_active' => true])) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (!in_array($user->role, ['admin', 'petugas', 'peminjam'], true)) {
                Auth::logout();
                return redirect()->back()->withErrors(['email' => 'Role tidak dikenali.']);
            }

            LogAktivitas::catat('Masuk (login) ke sistem');

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'petugas') {
                return redirect()->route('petugas.dashboard');
            }

            return redirect()->route('peminjam.katalog');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request) {
        LogAktivitas::catat('Keluar (logout) dari sistem');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showRegisterForm() {
        return view('auth.register');
    }   

    public function register(Request $request) {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'no_hp'    => ['nullable', 'digits_between:8,15'],
            'alamat'   => 'required|string|max:255',
        ], [
            'no_hp.digits_between' => 'No. HP hanya boleh berisi angka (8 sampai 15 digit), tanpa spasi atau tanda lain.',
        ]);

        // Role dipaksa 'peminjam' di server, input role dari form diabaikan
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
            'no_hp'    => $data['no_hp'] ?? null,
            'alamat'   => $data['alamat'],
            'role'     => 'peminjam',
        ]);

        // Langsung login setelah daftar
        Auth::login($user);
        $request->session()->regenerate();
        LogAktivitas::catat('Mendaftar dan masuk (login) ke sistem');

        return redirect()->route('peminjam.katalog')
            ->with('success', 'Pendaftaran berhasil. Selamat datang!');
    }
}