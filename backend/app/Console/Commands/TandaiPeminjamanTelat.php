<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

class TandaiPeminjamanTelat extends Command
{
    protected $signature = 'peminjaman:tandai-telat';

    protected $description = 'Ubah status peminjaman yang lewat tenggat dari dipinjam menjadi telat';

    public function handle(): int
    {
        // Sengaja pakai update massal lewat query (bukan $peminjaman->update()),
        // supaya PeminjamanObserver tidak jalan. Observer mencatat log dengan
        // Auth::id(), yang bernilai null saat dijalankan dari scheduler, dan
        // kolom log_aktivitas.user_id tidak boleh null.
        $jumlah = Peminjaman::where('status', 'dipinjam')
            ->whereDate('tgl_kembali_plan', '<', today())
            ->update(['status' => 'telat']);

        $this->info("{$jumlah} peminjaman ditandai telat.");

        return self::SUCCESS;
    }
}