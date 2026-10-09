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
}