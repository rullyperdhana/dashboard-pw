<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Daftar Gaji Pegawai</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #1565c0;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: left;
        }

        th {
            background-color: #1565c0;
            color: white;
            font-weight: bold;
            font-size: 8px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
        }

        .summary {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #e3f2fd;
            border-radius: 5px;
        }

        .totals {
            font-weight: bold;
            background-color: #bbdefb !important;
        }
    </style>
</head>

<body>
    <h1>Laporan Daftar Gaji Pegawai</h1>
    <p class="subtitle">Periode: {{ $monthName }} {{ $year }}</p>

    <div class="summary">
        <strong>Total Pegawai:</strong> {{ count($data) }} |
        <strong>Total Anggaran:</strong> Rp {{ number_format($grandTotal, 0, ',', '.') }}
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">NIP</th>
                <th width="15%">Nama</th>
                <th width="12%">Jabatan</th>
                <th width="10%">UPT</th>
                <th width="14%">SKPD</th>
                <th width="9%">Gaji Pokok</th>
                <th width="6%">Pajak</th>
                <th width="6%">IWP</th>
                <th width="7%">Tunjangan</th>
                <th width="8%">Total Bersih</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['nip'] ?? '-' }}</td>
                    <td>{{ $item['nama'] ?? '-' }}</td>
                    <td>{{ $item['jabatan'] ?? '-' }}</td>
                    <td>{{ $item['upt'] ?? '-' }}</td>
                    <td>{{ $item['nama_skpd'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item['gaji_pokok'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item['pajak'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item['iwp'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item['tunjangan'] ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item['total_bersih'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="6">TOTAL</td>
                <td class="text-right">{{ number_format($sumGajiPokok, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($sumPajak, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($sumIwp, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($sumTunjangan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }}
    </div>
</body>

</html>