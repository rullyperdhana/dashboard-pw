<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payroll Statement - {{ $payment->month }}/{{ $payment->year }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1867C0;
            padding-bottom: 10px;
        }

        .header h1 {
            color: #1867C0;
            margin-bottom: 5px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            background: #f8fafc;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .details-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .footer {
            margin-top: 50px;
            text-align: right;
        }

        .currency {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN PEMBAYARAN GAJI PPPK</h1>
        <p>Periode: Bulan {{ $payment->month }} Tahun {{ $payment->year }}</p>
    </div>

    <table class="summary-table">
        <tr>
            <td><strong>Total Disalurkan:</strong></td>
            <td class="currency">Rp {{ number_format($payment->total_amoun, 0, ',', '.') }}</td>
            <td><strong>Total Pegawai:</strong></td>
            <td>{{ $payment->details->count() }} Orang</td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>DISBURSED</td>
            <td><strong>Tanggal Cetak:</strong></td>
            <td>{{ date('d F Y') }}</td>
        </tr>
    </table>

    <h3>Rincian Pembayaran</h3>
    <table class="details-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Pegawai / NIP</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Potongan</th>
                <th>Total Terma</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payment->details as $index => $detail)
                @php
                    $total = $detail->gaji_pokok + $detail->tunjangan - $detail->pajak - $detail->iwp - $detail->potongan;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $detail->employee->nama }}</strong><br>
                        <small>{{ $detail->employee->nip }}</small>
                    </td>
                    <td class="currency">{{ number_format($detail->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="currency">{{ number_format($detail->tunjangan, 0, ',', '.') }}</td>
                    <td class="currency">{{ number_format($detail->potongan + $detail->pajak + $detail->iwp, 0, ',', '.') }}
                    </td>
                    <td class="currency"><strong>{{ number_format($total, 0, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak secara otomatis oleh Sistem Payroll PPPK</p>
        <p>{{ date('d/m/Y H:i') }}</p>
    </div>
</body>

</html>