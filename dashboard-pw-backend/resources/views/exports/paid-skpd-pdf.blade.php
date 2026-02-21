<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan SKPD – Daftar Gaji</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .subtitle {
            text-align: center;
            color: #555;
            margin-bottom: 12px;
            font-size: 10px;
        }

        .summary {
            margin-bottom: 10px;
            padding: 6px 10px;
            background: #e8f5e9;
            border-radius: 4px;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px 5px;
        }

        th {
            background: #2e7d32;
            color: #fff;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }

        td.r {
            text-align: right;
        }

        td.c {
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f5faf5;
        }

        .totals {
            font-weight: bold;
            background: #c8e6c9 !important;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>

<body>
    <h1>Laporan SKPD – Daftar Gaji</h1>
    <p class="subtitle">
        Periode: {{ $monthName }} {{ $year }}
        @if(isset($type) && $type !== 'all')
            &nbsp;|&nbsp;
            {{ ['pns' => 'PNS', 'pppk' => 'PPPK Penuh Waktu', 'pw' => 'PPPK Paruh Waktu'][$type] ?? strtoupper($type) }}
        @endif
    </p>

    <div class="summary">
        <strong>Total SKPD:</strong> {{ count($data) }} &nbsp;|&nbsp;
        <strong>Total Pegawai:</strong> {{ $totalEmployees }} &nbsp;|&nbsp;
        <strong>Total Bersih:</strong> Rp {{ number_format($grandTotal, 0, ',', '.') }}
    </div>

    @if(isset($mode) && $mode === 'detail')
        {{-- ── Detail table: PNS / PPPK Penuh Waktu ────────────────────────── --}}
        <table>
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="10%">Kode SKPD</th>
                    <th width="20%">Nama SKPD</th>
                    <th width="4%">PEG</th>
                    <th>GAPOK</th>
                    <th>TJISTRI</th>
                    <th>TJANAK</th>
                    <th>TJTPP</th>
                    <th>TJESELON</th>
                    <th>TJFUNGSI</th>
                    <th>TJBERAS</th>
                    <th>TJPAJAK</th>
                    <th>TJUMUM</th>
                    <th>TBILAT</th>
                    <th>KOTOR</th>
                    <th>PIWP</th>
                    <th>PIWP2</th>
                    <th>PIWP8</th>
                    <th>PPAJAK</th>
                    <th>POTONGAN</th>
                    <th>BERSIH</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totals = array_fill_keys([
                        'jumlah_pegawai',
                        'gapok',
                        'tj_istri',
                        'tj_anak',
                        'tj_tpp',
                        'tj_eselon',
                        'tj_fungsi',
                        'tj_beras',
                        'tj_pajak',
                        'tj_umum',
                        'tj_bilat',
                        'kotor',
                        'pot_iwp',
                        'pot_iwp2',
                        'pot_iwp8',
                        'pot_pajak',
                        'total_potongan',
                        'bersih'
                    ], 0);
                @endphp
                @foreach($data as $idx => $item)
                    @php $item = (array) $item;
                        foreach ($totals as $k => $v)
                    $totals[$k] += ($item[$k] ?? 0); @endphp
                    <tr>
                        <td class="c">{{ $idx + 1 }}</td>
                        <td>{{ $item['kode_skpd'] ?? '-' }}</td>
                        <td>{{ $item['nama_skpd'] ?? '-' }}</td>
                        <td class="c">{{ $item['jumlah_pegawai'] ?? 0 }}</td>
                        <td class="r">{{ number_format($item['gapok'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_istri'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_anak'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_tpp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_eselon'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_fungsi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_beras'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_pajak'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_umum'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['tj_bilat'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['kotor'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['pot_iwp'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['pot_iwp2'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['pot_iwp8'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['pot_pajak'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['total_potongan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r"><strong>{{ number_format($item['bersih'] ?? 0, 0, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td colspan="3">TOTAL</td>
                    <td class="c">{{ $totals['jumlah_pegawai'] }}</td>
                    <td class="r">{{ number_format($totals['gapok'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_istri'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_anak'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_tpp'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_eselon'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_fungsi'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_beras'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_pajak'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_umum'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['tj_bilat'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['kotor'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['pot_iwp'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['pot_iwp2'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['pot_iwp8'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['pot_pajak'], 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($totals['total_potongan'], 0, ',', '.') }}</td>
                    <td class="r"><strong>{{ number_format($totals['bersih'], 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

    @else
        {{-- ── Summary table: Gabungan / PPPK Paruh Waktu ──────────────────── --}}
        <table>
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="10%">Kode SKPD</th>
                    <th width="32%">Nama SKPD</th>
                    <th width="7%">Jml Peg</th>
                    <th width="12%">Gaji Pokok</th>
                    <th width="12%">Tunjangan</th>
                    <th width="12%">Potongan</th>
                    <th width="12%">Total Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $idx => $item)
                    @php $item = (array) $item; @endphp
                    <tr>
                        <td class="c">{{ $idx + 1 }}</td>
                        <td>{{ $item['kode_skpd'] ?? '-' }}</td>
                        <td>{{ $item['nama_skpd'] ?? '-' }}</td>
                        <td class="c">{{ $item['employee_count'] ?? 0 }}</td>
                        <td class="r">{{ number_format($item['total_gaji_pokok'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['total_tunjangan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r">{{ number_format($item['total_potongan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="r"><strong>{{ number_format($item['total_bersih'] ?? 0, 0, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
                <tr class="totals">
                    <td colspan="3">TOTAL</td>
                    <td class="c">{{ $totalEmployees }}</td>
                    <td class="r">{{ number_format($sumGajiPokok, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($sumTunjangan, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format(isset($sumPotongan) ? $sumPotongan : 0, 0, ',', '.') }}</td>
                    <td class="r"><strong>{{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">Generated on {{ now()->format('d M Y H:i') }}</div>
</body>

</html>