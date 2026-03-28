<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'Daftar Pembayaran' }} PPPK Paruh Waktu</title>
    <style>
        body { font-family: sans-serif; font-size: 8pt; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px; font-size: 7.5pt; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { margin-top: 20px; font-style: italic; font-size: 7pt; }
        .debug-banner { background: #ffeb3b; padding: 10px; border: 2px solid #fbc02d; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="debug-banner">
        KONFIRMASI: Berhasil Menemukan {{ count($data) }} Grup SKPD (Total {{ $recordCount ?? 0 }} Pegawai)
    </div>

    <div class="header">
        <h2 style="margin:0;">PEMERINTAH PROVINSI KALIMANTAN SELATAN</h2>
        <h3 style="margin:5px 0;">DAFTAR PEMBAYARAN {{ strtoupper($title ?? 'THR') }} PEGAWAI PPPK PARUH WAKTU</h3>
        <p style="margin:0;">TAHUN {{ $year }} - BULAN {{ strtoupper($thrMonthName ?? '') }}</p>
    </div>

    @foreach($data as $skpd)
        <div style="margin-bottom: 30px;">
            <p style="font-weight: bold; font-size: 10pt; margin-bottom: 5px;">UNIT KERJA: {{ $skpd['skpd_name'] ?? 'N/A' }}</p>

            @foreach($skpd['pptk_groups'] as $pptk)
                <p style="margin: 5px 0; font-weight: bold;">PPTK: {{ $pptk['pptk_nama'] }}</p>
                
                @foreach($pptk['sub_giat_groups'] as $subGiat)
                    <p style="margin: 2px 0 5px 10px; font-style: italic;">Sub Kegiatan: {{ $subGiat['sub_giat_name'] }}</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th width="30">NO</th>
                                <th width="150">NAMA / NIP</th>
                                <th width="80">GOLONGAN</th>
                                <th width="100">JABATAN</th>
                                <th>JUMLAH DITERIMA (Rp)</th>
                                <th width="80">TANDA TANGAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subGiat['employees'] as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $item['nama'] }}</strong><br>
                                        {{ $item['nip'] }}
                                    </td>
                                    <td class="text-center">{{ $item['golongan'] }}</td>
                                    <td>{{ $item['jabatan'] }}</td>
                                    <td class="text-right">{{ number_format($item['payroll_amount'], 0, ',', '.') }}</td>
                                    <td style="height: 35px;"></td>
                                </tr>
                            @endforeach
                            <tr style="background:#eee; font-weight:bold;">
                                <td colspan="4" class="text-right">SUB TOTAL SUBGIAT:</td>
                                <td class="text-right">{{ number_format($subGiat['subtotal_thr'], 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                @endforeach
                
                <p style="text-align: right; font-weight: bold;">Total PPTK {{ $pptk['pptk_nama'] }}: Rp {{ number_format($pptk['total_pptk_thr'], 0, ',', '.') }}</p>
            @endforeach

            <!-- Bagian Tanda Tangan -->
            <div style="margin-top: 20px; width: 100%;">
                <table style="border: none;">
                    <tr>
                        <td width="33%" style="border: none; vertical-align: top;">
                            @if(isset($skpd['signatory']))
                                Setuju Dibayar,<br>
                                {{ $skpd['signatory']['jabatan_bendahara'] ?? 'Bendahara Pengeluaran' }}<br><br><br><br>
                                <strong>{{ $skpd['signatory']['nama_bendahara'] ?? '........................' }}</strong><br>
                                NIP. {{ $skpd['signatory']['nip_bendahara'] ?? '........................' }}
                            @endif
                        </td>
                        <td width="33%" style="border: none; text-align: center; vertical-align: top;">
                            <br>Mengetahui,<br>
                            PPTK<br><br><br><br>
                            <strong>{{ $skpd['pptk_groups'][0]['pptk_nama'] ?? '........................' }}</strong><br>
                            NIP. {{ $skpd['pptk_groups'][0]['pptk_nip'] ?? '........................' }}
                        </td>
                        <td width="33%" style="border: none; vertical-align: top;">
                            @if(isset($skpd['signatory']))
                                Banjarmasin, {{ $printDate }}<br>
                                {{ $skpd['signatory']['jabatan_kepala'] ?? 'Pengguna Anggaran' }}<br><br><br><br>
                                <strong>{{ $skpd['signatory']['nama_kepala'] ?? '........................' }}</strong><br>
                                NIP. {{ $skpd['signatory']['nip_kepala'] ?? '........................' }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="page-break-after: always;"></div>
        </div>
    @endforeach
</body>
</html>