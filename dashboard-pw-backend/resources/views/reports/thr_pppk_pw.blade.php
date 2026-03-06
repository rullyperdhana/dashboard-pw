<!DOCTYPE html>
<html>

<head>
    <title>Daftar THR PPPK Paruh Waktu</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2,
        .header h3 {
            margin: 2px 0;
        }

        .footer {
            margin-top: 20px;
            font-style: italic;
            font-size: 8px;
        }

        .total-row {
            font-weight: bold;
            background-color: #eee;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>DAFTAR PEMBAYARAN THR PEGAWAI PPPK PARUH WAKTU</h2>
        <h3>TAHUN {{ $year }} (PEMBAYARAN BULAN {{ strtoupper($thrMonthName) }})</h3>
        <p>Dasar Perhitungan: Gaji Pokok Pebruari ({{ $nMonths }}/12)</p>
    </div>

    @foreach($data as $group)
        <div style="page-break-inside: avoid; margin-bottom: 20px;">
            <h3 style="margin-bottom: 5px; color: #333; border-bottom: 2px solid #primary;">
                SKPD: {{ $group['skpd_name'] }}
            </h3>
            <table>
                <thead>
                    <tr>
                        <th width="30">No</th>
                        <th width="120">NIP</th>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th width="100">Gapok Basis</th>
                        <th width="80">Masa Kerja</th>
                        <th width="110">Besaran THR</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['employees'] as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item['nip'] }}</td>
                            <td>{{ $item['nama'] }}</td>
                            <td>{{ $item['jabatan'] }}</td>
                            <td class="text-right">{{ number_format($item['gapok_basis'], 0, ',', '.') }}</td>
                            <td class="text-center">{{ $item['n_months'] }} Bln</td>
                            <td class="text-right">{{ number_format($item['thr_amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="6" class="text-right">SUBTOTAL {{ $group['skpd_name'] }} &nbsp;</td>
                        <td class="text-right">{{ number_format($group['subtotal_thr'], 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div style="margin-top: 30px; border: 2px solid #000; padding: 10px; background-color: #f9f9f9;">
        <table style="margin-top: 0; border: none;">
            <tr style="border: none; background: none;">
                <td style="border: none; font-size: 14px; font-weight: bold;">TOTAL KESELURUHAN THR</td>
                <td style="border: none; font-size: 14px; font-weight: bold;" class="text-right">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px;">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 60%; vertical-align: bottom;">
                    <div class="footer">
                        Dokumen ini dihasilkan secara otomatis oleh Sistem PPPK Payroll Dashboard.<br>
                        Keaslian dokumen dapat diverifikasi melalui kode QR di samping.<br>
                        Dicetak pada: {{ $printDate }}
                    </div>
                </td>
                <td style="border: none; width: 40%; text-align: right;">
                    @if(isset($qrCode))
                        <div
                            style="display: inline-block; text-align: center; border: 1px solid #ddd; padding: 5px; background: white;">
                            <img src="{{ $qrCode }}" alt="QR Code Verification" width="100" height="100">
                            <div style="font-size: 8px; margin-top: 5px;">VERIFIKASI SISTEM</div>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>