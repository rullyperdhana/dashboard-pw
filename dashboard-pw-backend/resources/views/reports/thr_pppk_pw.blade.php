<!DOCTYPE html>
<html>

<head>
    <title>{{ $title ?? 'Daftar Pembayaran' }} PPPK Paruh Waktu</title>
    <style>
        @page {
            margin: 120px 30px 60px 30px;
            /* top, right, bottom, left */
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
        }

        header {
            position: fixed;
            top: -90px;
            left: 0px;
            right: 0px;
            height: 70px;
            text-align: center;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
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

        .page-number:after {
            content: "Halaman " counter(page);
        }
    </style>
</head>

<body>
    <footer>
        <span class="page-number"></span>
    </footer>

    <header>
        <h2 style="margin:0; padding:0;">PEMERINTAH PROVINSI KALIMANTAN SELATAN <br>DAFTAR PEMBAYARAN TUNJANGAN HARI RAYA (THR) PEGAWAI PPPK
            PARUH WAKTU</h2>
        <h3 style="margin:5px 0 0 0; padding:0;">TAHUN {{ $year }} (PEMBAYARAN BULAN {{ strtoupper($thrMonthName) }})
        </h3>
        <p style="margin:5px 0 0 0; padding:0; font-size: 10px;">Dasar Perhitungan: {{ $calculationBasis }}</p>
    </header>

    <main>

        @foreach($data as $skpd)
            <div style="margin-bottom: 20px;">
                <h1 style="text-align: center; border-bottom: 3px double #000; padding-bottom: 5px; margin-bottom: 15px;">
                    SKPD: {{ $skpd['skpd_name'] }}
                </h1>

                @foreach($skpd['pptk_groups'] as $pptk)
                    <div style="margin-bottom: 30px; border: 1px solid #ddd; padding: 10px; page-break-inside: avoid;">
                        <div style="background: #f9f9f9; padding: 5px 10px; margin-bottom: 10px; border-left: 5px solid #007bff;">
                            <h3 style="margin: 0; color: #333;">PPTK: {{ $pptk['pptk_nama'] }}</h3>
                            <p style="margin: 2px 0 0 0; font-size: 9px; color: #666;">NIP. {{ $pptk['pptk_nip'] }} | {{ $pptk['pptk_jabatan'] }}</p>
                        </div>

                        @foreach($pptk['sub_giat_groups'] as $subGiat)
                            <div style="margin-bottom: 15px;">
                                <h4 style="margin-top: 0; margin-bottom: 5px; color: #444; border-bottom: 1px solid #eee;">
                                    Sub Kegiatan: {{ $subGiat['sub_giat_name'] }}
                                </h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="30">No</th>
                                            <th width="110">NIP</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th width="90">Gapok Basis</th>
                                            <th width="70">Masa Kerja</th>
                                            <th width="100">Besaran {{ $title ?? 'Pembayaran' }}</th>
                                            <th width="140">Tanda Tangan / Penerima</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subGiat['employees'] as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td>{{ $item['nip'] }}</td>
                                                <td>{{ $item['nama'] }}</td>
                                                <td>{{ $item['jabatan'] }}</td>
                                                <td class="text-right">{{ number_format($item['gapok_basis'], 0, ',', '.') }}</td>
                                                <td class="text-center">{{ $item['n_months'] }} Bln</td>
                                                <td class="text-right">{{ number_format($item['payroll_amount'], 0, ',', '.') }}</td>
                                                <td style="padding-top: 5px; height: 35px; vertical-align: top;">
                                                    @if(($index + 1) % 2 != 0)
                                                        {{ $index + 1 }}.
                                                    @else
                                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $index + 1 }}.
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="total-row">
                                            <td colspan="6" class="text-right">SUBTOTAL SUB KEGIATAN &nbsp;</td>
                                            <td class="text-right">{{ number_format($subGiat['subtotal_thr'] ?? $subGiat['subtotal_payroll'], 0, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>

                                {{-- Signature and System Verification per Sub-Activity --}}
                                <div style="page-break-inside: avoid; margin-top: 15px;">
                                    <table style="border: none; margin-top: 0; margin-bottom: 10px;">
                                        <tr style="border: none; background: none;">
                                            <td style="border: none; width: 45%; text-align: center; padding: 0;">
                                                <p style="margin-bottom: 50px; font-weight: bold;">
                                                    Mengetahui/Menyetujui,<br>
                                                    {{ $skpd['signatory']['jabatan_kepala'] ?? 'Pengguna Anggaran' }}
                                                </p>
                                                <p style="margin-bottom: 0;">
                                                    <span style="text-decoration: underline; font-weight: bold;">{{ $skpd['signatory']['nama_kepala'] ?? '..................................' }}</span><br>
                                                    NIP. {{ $skpd['signatory']['nip_kepala'] ?? '..................................' }}
                                                </p>
                                            </td>
                                            <td style="border: none; width: 10%;"></td>
                                            <td style="border: none; width: 45%; text-align: center; padding: 0;">
                                                <p style="margin-bottom: 50px; font-weight: bold;">
                                                    Banjarmasin, {{ $printDate }}<br>
                                                    PPTK,
                                                </p>
                                                <p style="margin-bottom: 0;">
                                                    <span style="text-decoration: underline; font-weight: bold;">{{ $pptk['pptk_nama'] }}</span><br>
                                                    NIP. {{ $pptk['pptk_nip'] }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table style="border: none;">
                                        <tr style="border: none;">
                                            <td style="border: none; width: 70%; vertical-align: bottom;">
                                                <div class="footer" style="font-size: 10px; color: #555;">
                                                    <strong>KEABSAHAN DOKUMEN:</strong><br>
                                                    Dokumen ini dihasilkan secara otomatis oleh Sistem PPPK Payroll Dashboard yang
                                                    menggunakan metode: {{ $thrMethod == 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12' }}.<br>
                                                    Keaslian dokumen dapat diverifikasi melalui kode QR di samping.<br>
                                                    Dicetak pada: {{ $printDate }}
                                                </div>
                                            </td>
                                            <td style="border: none; width: 30%; text-align: right;">
                                                @if(isset($subGiat['qr_code']))
                                                    <div style="display: inline-block; text-align: center; border: 1px solid #ddd; padding: 5px; background: white;">
                                                        <img src="{{ $subGiat['qr_code'] }}" alt="QR Code Verification" width="70" height="70">
                                                        <div style="font-size: 7px; margin-top: 3px;">VERIFIKASI SISTEM</div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div style="page-break-after: always;"></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
            @if(!$loop->last)
                <div style="page-break-after: always;"></div>
            @endif
        @endforeach
    </main>
</body>

</html>