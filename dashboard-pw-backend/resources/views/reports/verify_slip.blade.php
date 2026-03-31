<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - SIP-Gaji</title>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --error: #ef4444;
            --bg: #f8fafc;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #1e293b;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .icon-error { background: rgba(239, 68, 68, 0.1); color: var(--error); }
        
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { color: #64748b; line-height: 1.6; }
        
        .details {
            margin-top: 2rem;
            text-align: left;
            background: #f1f5f9;
            padding: 1.25rem;
            border-radius: 1rem;
        }
        .detail-item { margin-bottom: 0.75rem; }
        .detail-item:last-child { margin-bottom: 0; }
        .label { font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
        .value { font-weight: 600; color: #334155; }
        
        .footer { margin-top: 2rem; font-size: 0.75rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <div class="icon icon-success">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1>Dokumen Valid</h1>
            <p>{{ $message }}</p>
            
            <div class="details">
                <div class="detail-item">
                    <div class="label">Nama Pegawai</div>
                    <div class="value">{{ $data->nama }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">NIP</div>
                    <div class="value">{{ $data->nip }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Unit Kerja (SKPD)</div>
                    <div class="value">{{ $data->skpd }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Masa Penggajian</div>
                    <div class="value">{{ $data->bulan }}/{{ $data->tahun }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Bersih</div>
                    <div class="value">Rp {{ number_format($data->bersih, 0, ',', '.') }}</div>
                </div>
            </div>
        @else
            <div class="icon icon-error">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1>Dokumen Tidak Valid</h1>
            <p>{{ $message }}</p>
        @endif
        
        <div class="footer">
            &copy; 2026 SIP-Gaji Pemerintah Provinsi. <br>
            Sistem Informasi Penggajian Terpadu.
        </div>
    </div>
</body>
</html>
