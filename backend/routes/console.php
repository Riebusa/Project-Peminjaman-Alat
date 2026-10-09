<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tandai peminjaman yang lewat tenggat setiap hari, sesaat setelah pergantian hari
Schedule::command('peminjaman:tandai-telat')->dailyAt('00:05');