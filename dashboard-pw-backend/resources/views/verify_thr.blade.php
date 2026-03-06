<!DOCTYPE html>
<html>

<head>
    <title>DEBUG VERIFIKASI</title>
</head>

<body style="padding: 20px; font-family: sans-serif;">
    <h1 style="color: green;">✔ DOKUMEN VALID (SISTEM)</h1>
    <hr>
    <div style="background: #f0f0f0; padding: 15px; border-radius: 8px;">
        <p><strong>Jenis Dokumen:</strong> THR PPPK Paruh Waktu</p>
        <p><strong>Periode:</strong> {{ $period ?? 'Tidak Diketahui' }}</p>
        <p><strong>Total Nilai:</strong> Rp {{ $total ?? '0' }}</p>
        <p><strong>Waktu Cetak:</strong> {{ $date ?? '-' }}</p>
    </div>
    <p style="margin-top: 20px; font-size: 12px; color: #666;">
        ID Verifikasi: {{ request()->fullUrl() }}
    </p>
    <hr>
    <p>&copy; {{ date('Y') }} BPKAD Kab. Seruyan</p>
</body>

</html>