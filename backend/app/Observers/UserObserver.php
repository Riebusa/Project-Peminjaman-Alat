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
        unset($perubahan['updated_at'], $perubahan['remember_token']);

        // Ganti password: catat kejadiannya, jangan pernah nilainya
        if (array_key_exists('password', $perubahan)) {
            LogAktivitas::catat("Mengganti password akun '{$user->name}'");
            unset($perubahan['password']);
        }

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