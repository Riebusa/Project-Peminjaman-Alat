@props(['status'])

@php
    $kelas = match($status) {
        'diajukan' => 'bg-amber-100 text-amber-800 border-amber-200',
        'dipinjam' => 'bg-blue-100 text-blue-800 border-blue-200',
        'telat' => 'bg-red-100 text-red-800 border-red-200',
        'menunggu_pengembalian' => 'bg-purple-100 text-purple-800 border-purple-200',
        'dikembalikan' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'ditolak' => 'bg-slate-200 text-slate-700 border-slate-300',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-block text-xs font-bold px-2.5 py-1 rounded-md border uppercase {$kelas}"]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>