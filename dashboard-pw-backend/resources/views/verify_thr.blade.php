<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - Payroll Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .verify-card {
            max-width: 500px;
            margin: 50px auto;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .verify-header {
            background: linear-gradient(135deg, #1a906b 0%, #0d6efd 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .status-icon {
            font-size: 60px;
            margin-bottom: 20px;
            display: inline-block;
            background: white;
            color: #1a906b;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            color: #6c757d;
            font-weight: 500;
        }

        .detail-value {
            color: #212529;
            font-weight: 700;
            text-align: right;
        }

        .badge-valid {
            background-color: #1a906b;
            font-size: 1.2rem;
            padding: 10px 25px;
            border-radius: 50px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card verify-card">
            <div class="verify-header">
                <div class="status-icon">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <h2 class="mb-0">DOKUMEN VALID</h2>
                <p class="opacity-75">Tervalidasi secara Sistem</p>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="badge badge-valid">Daftar Pembayaran THR</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Jenis Dokumen</span>
                    <span class="detail-value">THR PPPK Paruh Waktu</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Periode Pembayaran</span>
                    <span class="detail-value">{{ $period ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Nilai THR</span>
                    <span class="detail-value">Rp {{ $total ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Waktu Pencetakan</span>
                    <span class="detail-value">{{ $date ?? '-' }}</span>
                </div>

                <div class="mt-4 p-3 bg-light rounded text-center">
                    <small class="text-muted">
                        Dokumen ini adalah salinan digital resmi yang dihasilkan oleh
                        <strong>Sistem Payroll BPKAD Provinsi Kalimantan Selatan</strong>.
                    </small>
                </div>
            </div>
            <div class="card-footer text-center py-3 bg-white border-0">
                <p class="text-muted small mb-0">&copy; {{ date('Y') }} BPKAD PROVINSI KALIMANTAN SELATAN</p>
            </div>
        </div>
    </div>
</body>

</html>