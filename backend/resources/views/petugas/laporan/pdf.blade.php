<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman Alat</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 2px 0; }
        .sub { font-size: 10px; color: #64748b; margin: 0 0 14px 0; }
        .meta { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .meta td { padding: 2px 0; font-size: 10px; }
        .meta td.label { width: 110px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 5px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.data td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .status { font-weight: bold; text-transform: uppercase; font-size: 9px; }
        .ringkasan { margin-top: 14px; width: 100%; border-collapse: collapse; }
        .ringkasan td { padding: 3px 0; font-size: 10px; }
        .ringkasan td.label { width: 140px; color: #64748b; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { width: 50%; font-size: 10px; }
        .kosong { text-align: center; padding: 18px; color: #64748b; }
    </style>
</head>
<body>
    <h1>Laporan Peminjaman Alat</h1>
    <p class="sub">Rekapitulasi transaksi peminjaman dan pengembalian</p>

    <table class="meta">
        <tr>
            <td class="label">Periode (tgl pinjam)</td>
            <td>: {{ \Carbon\Carbon::parse($filter['start_date'])->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($filter['end_date'])->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>: {{ $filter['status'] === 'semua' ? 'Semua Status' : ucwords(str_replace('_', ' ', $filter['status'])) }}</td>
        </tr>
        <tr>
            <td class="label">Dicetak oleh</td>
            <td>: {{ $petugas }} pada {{ $dicetak->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="center" style="width: 4%;">No</th>
                <th style="width: 17%;">Peminjam</th>
                <th style="width: 24%;">Alat</th>
                <th style="width: 10%;">Tgl Pinjam</th>
                <th style="width: 10%;">Tenggat</th>
                <th style="width: 10%;">Tgl Kembali</th>
                <th style="width: 12%;">Status</th>
                <th class="right" style="width: 13%;">Denda</th>
            </tr>
        </thead>
        <tbody>
            @forelse($peminjamans as $p)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $p->user->name ?? 'User Terhapus' }}<br><span style="color:#64748b;">#TRX-{{ $p->id }}</span></td>
                    <td>
                        @foreach($p->detailPinjam as $detail)
                            {{ $detail->alat->nama_alat ?? 'Alat Terhapus' }} ({{ $detail->jumlah }}x)@if(!$loop->last)<br>@endif
                        @endforeach
                    </td>
                    <td>{{ \Carbon\Carbon::parse($p->tgl_pinjam)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->tgl_kembali_plan)->format('d/m/Y') }}</td>
                    <td>{{ $p->pengembalian ? \Carbon\Carbon::parse($p->pengembalian->tgl_kembali)->format('d/m/Y') : '-' }}</td>
                    <td><span class="status">{{ str_replace('_', ' ', $p->status) }}</span></td>
                    <td class="right">{{ $p->pengembalian ? 'Rp ' . number_format($p->pengembalian->denda, 0, ',', '.') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="kosong">Tidak ada transaksi pada periode dan status yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="ringkasan">
        <tr>
            <td class="label">Total transaksi</td>
            <td>: {{ $ringkasan['total'] }}</td>
        </tr>
        <tr>
            <td class="label">Total denda</td>
            <td>: Rp {{ number_format($ringkasan['total_denda'], 0, ',', '.') }}</td>
        </tr>
        @foreach($ringkasan['per_status'] as $status => $jumlah)
            <tr>
                <td class="label">{{ ucwords(str_replace('_', ' ', $status)) }}</td>
                <td>: {{ $jumlah }}</td>
            </tr>
        @endforeach
    </table>

    <table class="ttd">
        <tr>
            <td></td>
            <td class="center">
                Petugas,<br><br><br><br>
                <strong>{{ $petugas }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>