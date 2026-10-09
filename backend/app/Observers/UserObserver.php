<?php

namespace App\Observers;

use App\Models\LogAktivitas;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        LogAktivitas::catat("Mendaftarkan user baru: '{$user->name}' (Role: {$user->role})");
    }

    public function updated(User $user): void
    {
        $perubahan = $user->getChanges();
        // Jangan catat password dan token demi keamanan
        unset($perubahan['updated_at'], $perubahan['password'], $perubahan['remember_token']);

        // Perubahan status aktif dicatat dengan kalimat khusus
        if (array_key_exists('is_active', $perubahan)) {
            $aksi = $user->is_active ? 'MENGAKTIFKAN' : 'MENONAKTIFKAN';
            LogAktivitas::catat("{$aksi} akun user '{$user->name}'");
            unset($perubahan['is_active']);
        }

        if (empty($perubahan)) {
            return;
        }

        $detail = [];
        foreach ($perubahan as $kolom => $nilaiBaru) {
            $nilaiLama = $user->getOriginal($kolom) ?? 'kosong';
            $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
        }

        LogAktivitas::catat("Memperbarui data user '{$user->name}'. Detail: " . implode(', ', $detail));
    }

    public function deleted(User $user): void
    {
        LogAktivitas::catat("Menghapus user: '{$user->name}'");
    }
}