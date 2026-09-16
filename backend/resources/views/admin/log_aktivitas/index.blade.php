@extends('layouts.dev')

@section('content')
<!-- Header Log Minimalis -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 border-b border-slate-200 pb-5">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Audit Trail & Log Aktivitas</h2>
        <p class="text-slate-500 text-sm mt-1">Pantau seluruh rekam jejak dan riwayat perubahan data di dalam sistem.</p>
    </div>
</div>

<div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4 font-medium w-48">Waktu & Pelaku</th>
                    <th class="px-6 py-4 font-medium">Rincian Aktivitas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <!-- LOGIC PHP UNTUK MENDETEKSI JENIS AKTIVITAS -->
                    @php
                        $teks = strtolower($log->aktivitas);
                        $badge = 'INFO';
                        $color = 'bg-slate-100 text-slate-700 border-slate-200';
                        
                        // Deteksi Tipe Aksi
                        if (str_contains($teks, 'menambahkan') || str_contains($teks, 'mendaftarkan') || str_contains($teks, 'membuat')) {
                            $badge = 'CREATE';
                            $color = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                        } elseif (str_contains($teks, 'memperbarui')) {
                            $badge = 'UPDATE';
                            $color = 'bg-blue-100 text-blue-700 border-blue-200';
                        } elseif (str_contains($teks, 'menghapus')) {
                            $badge = 'DELETE';
                            $color = 'bg-red-100 text-red-700 border-red-200';
                        } elseif (str_contains($teks, 'menerima') || str_contains($teks, 'menyetujui')) {
                            $badge = 'ACTION';
                            $color = 'bg-purple-100 text-purple-700 border-purple-200';
                        }

                        // Memisahkan Judul Aktivitas dan Detailnya
                        $parts = explode('. Detail: ', $log->aktivitas);
                        $judulAktivitas = $parts[0];
                        $detailAktivitas = $parts[1] ?? null;
                    @endphp

                    <tr class="hover:bg-slate-50 transition-colors group">
                        <!-- Kolom Waktu & User -->
                        <td class="px-6 py-4 align-top">
                            <p class="text-sm font-bold text-slate-800">{{ $log->user->name ?? 'Sistem / Terhapus' }}</p>
                            <p class="text-xs text-slate-500 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                            </p>
                        </td>

                        <!-- Kolom Rincian -->
                        <td class="px-6 py-4 align-top">
                            <!-- Judul & Badge -->
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border uppercase tracking-wider {{ $color }}">
                                    {{ $badge }}
                                </span>
                                <span class="text-sm font-semibold text-slate-700">
                                    {{ $judulAktivitas }}
                                </span>
                            </div>
                            
                            <!-- Menampilkan Detail (Jika ada) -->
                            @if($detailAktivitas)
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(explode(', ', $detailAktivitas) as $detail)
                                        @php
                                            // Memecah "kolom (lama ➔ baru)"
                                            $dParts = explode(' (', rtrim($detail, ')'));
                                            $kolom = $dParts[0] ?? '';
                                            $nilai = $dParts[1] ?? '';
                                            $nilaiParts = explode(' ➔ ', $nilai);
                                        @endphp

                                        <div class="inline-flex items-center text-xs bg-white border border-slate-200 rounded-md px-2.5 py-1.5 shadow-sm">
                                            <strong class="font-medium text-slate-500 mr-2 uppercase tracking-wide text-[10px]">{{ $kolom }}:</strong> 
                                            
                                            @if(count($nilaiParts) == 2)
                                                <!-- Nilai Lama (Dicoret) -->
                                                <span class="line-through text-slate-400">{{ $nilaiParts[0] }}</span> 
                                                <!-- Icon Panah -->
                                                <svg class="w-3 h-3 text-slate-400 mx-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg> 
                                                <!-- Nilai Baru -->
                                                <span class="font-bold text-blue-600">{{ $nilaiParts[1] }}</span>
                                            @else
                                                <span class="text-slate-700">{{ $detail }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center">
                            <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="text-base font-semibold text-slate-700">Belum Ada Riwayat</p>
                            <p class="text-sm text-slate-500 mt-1">Aktivitas user di dalam sistem akan otomatis tercatat di sini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection