<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogAktivitas extends Model
{
    use HasFactory;

    protected $table = 'log_aktivitas';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Catat aktivitas oleh user yang sedang login.
    // Dilewati kalau tidak ada pelaku (tinker, seeder, command).
    public static function catat(string $aktivitas): void
    {
        if (!Auth::check()) {
            return;
        }

        static::create([
            'user_id'   => Auth::id(),
            'aktivitas' => $aktivitas,
        ]);
    }
}