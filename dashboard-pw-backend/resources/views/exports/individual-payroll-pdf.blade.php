<!DOCTYPE html>
<html>
<head>
    <title>Trace Daftar Penggajian - {{ $employee->nama }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 16px; }
        .header p { margin: 5px 0 0; font-size: 12px; }
        
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 5px; vertical-align: top; }
        .info-table td.label { width: 120px; font-weight: bold; }
        .info-table td.separator { width: 10px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th { background-color: #f2f2f2; border: 1px solid #ccc; padding: 6px 4px; text-align: center; font-weight: bold; }
        .data-table td { border: 1px solid #ccc; padding: 5px 4px; text-align: right; }
        .data-table td.text-center { text-align: center; }
        .data-table td.text-left { text-align: left; }
        
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #777; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        
        @page { margin: 1cm; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Pencairan Daftar Penggajian Per Orang</h2>
        <p>Pegawai PPPK Paruh Waktu</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Nama Pegawai</td>
                <td class="separator">:</td>
                <td>{{ $employee->nama }}</td>
            </tr>
            <tr>
                <td class="label">NIP</td>
                <td class="separator">:</td>
                <td>{{ $employee->nip }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="separator">:</td>
                <td>{{ $employee->jabatan }}</td>
            </tr>
            <tr>
                <td class="label">SKPD</td>
                <td class="separator">:</td>
                <td>{{ $employee->skpd->nama_skpd ?? $employee->skpd }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th>Bulan / Tahun</th>
                <th>Gaji Pokok</th>
                <th>Tunjangan</th>
                <th>Potongan</th>
                <th>IWP / Pajak</th>
                <th>Total Bersih</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalGaji = 0; 
                $totalTunj = 0; 
                $totalPot = 0; 
                $totalIwpPajak = 0;
                $totalBersih = 0;
                $months = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
            @endphp
            @forelse($history as $index => $item)
                @php
                    $iwpPajak = $item->iwp + $item->pajak;
                    $totalGaji += $item->gaji_pokok;
                    $totalTunj += $item->tunjangan;
                    $totalPot += $item->potongan;
                    $totalIwpPajak += $iwpPajak;
                    $totalBersih += $item->total_amoun;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-left">{{ $months[$item->payment->month] ?? $item->payment->month }} {{ $item->payment->year }}</td>
                    <td>{{ number_format($item->gaji_pokok, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->tunjangan, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->potongan, 0, ',', '.') }}</td>
                    <td>{{ number_format($iwpPajak, 0, ',', '.') }}</td>
                    <td>{{ number_format($item->total_amoun, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Data penggajian tidak ditemukan</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-center">TOTAL</td>
                <td>{{ number_format($totalGaji, 0, ',', '.') }}</td>
                <td>{{ number_format($totalTunj, 0, ',', '.') }}</td>
                <td>{{ number_format($totalPot, 0, ',', '.') }}</td>
                <td>{{ number_format($totalIwpPajak, 0, ',', '.') }}</td>
                <td>{{ number_format($totalBersih, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ $generated_at }}
    </div>
</body>
</html>
