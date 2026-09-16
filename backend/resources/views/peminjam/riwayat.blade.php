<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Peminjam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Panel Peminjam</a>
            <div class="d-flex">
                <!-- Tombol kembali ke katalog -->
                <a href="{{ route('peminjam.kategori_alat') }}" class="btn btn-outline-light btn-sm me-2">Katalog Alat</a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-light btn-sm text-primary">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <h3 class="mb-3">Riwayat Peminjaman Saya</h3>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Status</th>
                            <th>Daftar Alat (Jumlah)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peminjamans as $index => $peminjaman)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d-m-Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d-m-Y') }}</td>
                                <td>
                                    @if($peminjaman->status == 'diajukan')
                                        <span class="badge bg-warning text-dark">Diajukan</span>
                                    @elseif($peminjaman->status == 'dipinjam')
                                        <span class="badge bg-info text-dark">Sedang Dipinjam</span>
                                    @elseif($peminjaman->status == 'selesai')
                                        <span class="badge bg-success">Selesai</span>
                                    @else
                                        <span class="badge bg-danger">{{ ucfirst($peminjaman->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <ul class="mb-0" style="padding-left: 20px;">
                                        @foreach($peminjaman->detailPinjam as $detail)
                                            <li>{{ $detail->alat->nama_alat ?? 'Alat Dihapus' }} ({{ $detail->jumlah }} unit)</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Anda belum memiliki riwayat peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>