<!DOCTYPE html>
<html>
<head>
    <title>{{ $title ?? 'Daftar Pembayaran' }} PPPK Paruh Waktu</title>
    <style>
        @page { margin: 1.5cm 1cm 1cm 1cm; }
        body { font-family: sans-serif; font-size: 8pt; color: #000; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { margin: 0; font-size: 11pt; text-transform: uppercase; }
        .header h3 { margin: 5px 0; font-size: 10pt; text-transform: uppercase; }
        .header p { margin: 0; font-size: 9pt; }
        
        .info-section { margin-bottom: 10px; font-weight: bold; }
        .info-row { margin-bottom: 3px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 5px; font-size: 7.5pt; word-wrap: break-word; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; text-transform: uppercase; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .signature-table { border: none !important; width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .signature-table td { border: none !important; padding: 0; vertical-align: top; text-align: center; font-size: 8.5pt; }
        .sig-name { padding-top: 65px; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
        
        .page-break { page-break-after: always; }
        .employee-sign { position: relative; height: 35px; }
        .sign-no { position: absolute; top: 2px; left: 2px; font-size: 6pt; color: #555; }
    </style>
</head>
<body>
    @foreach($data as $skpd)
        @foreach($skpd['pptk_groups'] as $pptk)
            @foreach($pptk['sub_giat_groups'] as $sgIndex => $subGiat)
                <div class="header">
                    <h2>PEMERINTAH PROVINSI KALIMANTAN SELATAN</h2>
                    <h3>DAFTAR PEMBAYARAN {{ strtoupper($title ?? 'THR') }} PEGAWAI PPPK PARUH WAKTU</h3>
                    <p>TAHUN {{ $year }} - BULAN {{ strtoupper($thrMonthName ?? '') }}</p>
                </div>

                <div class="info-section">
                    <div class="info-row">UNIT KERJA : {{ $skpd['skpd_name'] ?? 'N/A' }}</div>
                    <div class="info-row">PPTK : {{ $pptk['pptk_nama'] }}</div>
                    <div class="info-row">SUB KEGIATAN : {{ $subGiat['sub_giat_name'] }}</div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th width="20">NO</th>
                            <th width="190">NAMA / NIP</th>
                            <th>JABATAN</th>
                            <th width="105">JUMLAH DITERIMA (Rp)</th>
                            <th width="130">TANDA TANGAN</th>
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
                                <td>{{ $item['jabatan'] }}</td>
                                <td class="text-right">{{ number_format($item['payroll_amount'], 0, ',', '.') }}</td>
                                <td class="employee-sign">
                                    <span class="sign-no">{{ $index + 1 }}.</span>
                                    @if(($index + 1) % 2 != 0)
                                        ................................
                                    @else
                                        <div style="text-align: right;">................................</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr style="background:#eee; font-weight:bold;">
                            <td colspan="3" class="text-right text-bold">TOTAL SUB KEGIATAN:</td>
                            <td class="text-right">{{ number_format($subGiat['subtotal_thr'], 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Bagian Tanda Tangan Pejabat (Sesuai Dinamika SKPD) -->
                <table class="signature-table">
                    <tr>
                        <td width="55%">
                            <br>Mengetahui,<br>
                            Pejabat Pengelola Teknis Kegiatan
                            <div class="sig-name">
                                ( {{ $pptk['pptk_nama'] ?? '....................................' }} )
                            </div>
                            NIP. {{ $pptk['pptk_nip'] ?? '....................................' }}
                        </td>
                        <td width="45%">
                            Banjarbaru, {{ $printDate }}<br>
                            @if(isset($skpd['signatory']))
                                {{ strtoupper($skpd['signatory']['jabatan_bendahara'] ?? 'BENDAHARA PENGELUARAN PEMBANTU') }}
                                <div class="sig-name">
                                    ( {{ $skpd['signatory']['nama_bendahara'] ?? '....................................' }} )
                                </div>
                                NIP. {{ $skpd['signatory']['nip_bendahara'] ?? '....................................' }}
                            @else
                                BENDAHARA PENGELUARAN PEMBANTU
                                <div class="sig-name">
                                    ( .................................... )
                                </div>
                                NIP. ....................................
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="page-break"></div>
            @endforeach
        @endforeach
    @endforeach
</body>
</html>