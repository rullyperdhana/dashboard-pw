<!DOCTYPE html>
<html>

<head>
    <title>VERIFIKASI PEMBAYARAN GAJI</title>
</head>

<body style="padding: 20px; font-family: sans-serif; background-color: #f4f4f4;">
    <div
        style="max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h1 style="color: #2e7d32; text-align: center; margin-bottom: 5px;">✔ DOKUMEN VALID</h1>
        <p style="text-align: center; color: #666; margin-top: 0;">Sistem Payroll PPPK Paruh Waktu</p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <p style="margin: 5px 0;"><strong>Jenis Dokumen:</strong> Daftar Pembayaran Gaji</p>
            <p style="margin: 5px 0;"><strong>Periode:</strong> {{ $period ?? 'Tidak Diketahui' }}</p>
            <p style="margin: 5px 0;"><strong>Kegiatan:</strong> {{ $kegiatan ?? '-' }}</p>
            <p style="margin: 5px 0;"><strong>Sub Kegiatan:</strong> {{ $sub_kegiatan ?? '-' }}</p>
            <p style="margin: 15px 0 5px 0; font-size: 1.2em; color: #1e293b;"><strong>Total Bayar:</strong> <span
                    style="color: #1565c0;">Rp {{ $total ?? '0' }}</span></p>
            <p style="margin: 5px 0; font-size: 0.9em; color: #64748b;"><strong>Waktu Cetak:</strong> {{ $date ?? '-' }}
            </p>
        </div>

        <p style="margin-top: 25px; font-size: 11px; color: #94a3b8; text-align: center;">
            ID Verifikasi: {{ request()->fullUrl() }}
        </p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="text-align: center; font-size: 0.9em; color: #475569;">&copy; {{ date('Y') }} BPKAD Kab. Seruyan</p>
    </div>
</body>

</html>