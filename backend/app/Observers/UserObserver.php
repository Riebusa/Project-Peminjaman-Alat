<?php

namespace App\Observers;

use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    public function created(User $user)
    {
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Mendaftarkan user baru: '{$user->name}' (Role: {$user->role})"]);
    }

    public function updated(User $user)
    {
        $perubahan = $user->getChanges();
        unset($perubahan['updated_at'], $perubahan['password']); // Jangan log password demi keamanan
        
        if(!empty($perubahan)) {
            $detail = [];
            foreach ($perubahan as $kolom => $nilaiBaru) {
                $nilaiLama = $user->getOriginal($kolom) ?? 'kosong';
                $detail[] = "{$kolom} ({$nilaiLama} ➔ {$nilaiBaru})";
            }
            
            $teks = implode(', ', $detail);
            LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Memperbarui data user '{$user->name}'. Detail: {$teks}"]);
        }
    }

    public function deleted(User $user)
    {
        LogAktivitas::create(['user_id' => Auth::id(), 'aktivitas' => "Menghapus user: '{$user->name}'"]);
    }
}