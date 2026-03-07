<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Daftar Pembayaran Gaji - {{ $monthName }} {{ $payment->year }}</title>
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .underline {
            text-decoration: underline;
        }

        .header-title {
            font-size: 15px;
            margin-bottom: 2px;
        }

        .header-subtitle {
            font-size: 12px;
            margin-bottom: 25px;
        }

        .qr-code {
            position: absolute;
            top: 0;
            left: 0;
        }

        .info-box {
            width: 100%;
            border: 1px solid #000;
            padding: 12px;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-box td {
            padding: 3px 5px;
            vertical-align: top;
            border: none;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }

        table.main-table th,
        table.main-table td {
            border: 1px solid #000;
            padding: 8px 5px;
            word-wrap: break-word;
        }

        table.main-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .summary-box {
            width: 100%;
            border: 1.5px solid #000;
            padding: 10px;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .summary-box td {
            padding: 4px 10px;
            border: none;
        }

        .summary-wrapper {
            width: 45%;
            margin-left: auto;
            border: 1.5px solid #000;
            border-radius: 4px;
            overflow: hidden;
        }

        .item-row:nth-child(even) {
            background-color: #ffffff;
        }
    </style>
</head>

<body>
    @if($qrCode)
        <div class="qr-code">
            <img src="{{ $qrCode }}" width="75" height="75">
        </div>
    @endif

    <div class="text-center" style="margin-bottom: 25px;">
        <div class="header-title font-bold underline">DAFTAR PEMBAYARAN GAJI PEGAWAI</div>
        <div class="header-subtitle font-bold">Periode: {{ $monthName }} {{ $payment->year }}</div>
    </div>

    <table class="info-box">
        <tr>
            <td width="130" class="font-bold">Kegiatan</td>
            <td width="5">:</td>
            <td>[{{ $payment->rkaSetting->kode_giat }}] {{ $payment->rkaSetting->nama_giat }}</td>
        </tr>
        <tr>
            <td class="font-bold">Sub Kegiatan</td>
            <td>:</td>
            <td>[{{ $payment->rkaSetting->kode_sub_giat }}] {{ $payment->rkaSetting->nama_sub_giat }}</td>
        </tr>
        <tr>
            <td class="font-bold">Tanggal Pembayaran</td>
            <td>:</td>
            <td>
                @php
                    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $d = $payment->payment_dat ?: $payment->created_at;
                @endphp
                {{ $d->format('d') }} {{ $months[(int) $d->format('m')] }} {{ $d->format('Y') }}
            </td>
        </tr>
        <tr>
            <td class="font-bold">Total Pegawai</td>
            <td>:</td>
            <td>{{ $payment->details->count() }} Orang</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="150">Nama/NIP</th>
                <th width="110">Jabatan</th>
                <th width="80">Gaji Pokok</th>
                <th width="65">Pajak</th>
                <th width="65">IWP</th>
                <th width="90">Total</th>
                <th width="70">Tanda Terima</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumGP = 0;
                $sumPajak = 0;
                $sumIWP = 0;
                $sumTotal = 0;
            @endphp
            @foreach($payment->details as $index => $detail)
                @php
                    $sumGP += $detail->gaji_pokok;
                    $sumPajak += $detail->pajak;
                    $sumIWP += $detail->iwp;
                    $sumTotal += (float) $detail->total_amoun;
                @endphp
                <tr class="item-row">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ strtoupper($detail->employee->nama) }}</div>
                        <div style="font-size: 8px;">{{ $detail->employee->nip }}</div>
                    </td>
                    <td style="font-size: 8.5px;">{{ strtoupper($detail->employee->jabatan) }}</td>
                    <td class="text-right">{{ number_format($detail->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($detail->pajak, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($detail->iwp, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($detail->total_amoun, 0, ',', '.') }}</td>
                    <td class="text-center" style="font-size: 8px;">.......</td>
                </tr>
            @endforeach
            <tr class="total-row" style="background-color: #ffffff; font-weight: bold;">
                <td colspan="3" class="text-right" style="padding-right: 15px;">TOTAL: &nbsp;</td>
                <td class="text-right">{{ number_format($sumGP, 0, ',', '.') }}</td>
                <td class="text-right">
                    <div style="font-size: 8px; margin-bottom: -5px; color: #333;">Rp</div>
                    {{ number_format($sumPajak, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    <div style="font-size: 8px; margin-bottom: -5px; color: #333;">Rp</div>
                    {{ number_format($sumIWP, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    <div style="font-size: 8px; margin-bottom: -5px; color: #333;">Rp</div>
                    {{ number_format($sumTotal, 0, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-wrapper" style="width: 100%; border: 2px solid #000; margin-top: 10px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px 15px; font-size: 11px;">Total Gaji Pokok:</td>
                <td class="text-right" style="padding: 10px 15px; font-size: 11px;">Rp
                    {{ number_format($sumGP, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 15px; font-size: 11px;">Total Pajak:</td>
                <td class="text-right" style="padding: 5px 15px; font-size: 11px;">Rp
                    {{ number_format($sumPajak, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 15px; border-bottom: 2px solid #000; font-size: 11px;">Total IWP:</td>
                <td class="text-right" style="padding: 5px 15px; border-bottom: 2px solid #000; font-size: 11px;">Rp
                    {{ number_format($sumIWP, 0, ',', '.') }}
                </td>
            </tr>
            <tr class="font-bold" style="font-size: 14px;">
                <td style="padding: 12px 15px;">TOTAL PEMBAYARAN:</td>
                <td class="text-right" style="padding: 12px 15px;">Rp {{ number_format($sumTotal, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; font-size: 8px; color: #666; text-align: center;">
        Dokumen ini diterbitkan oleh Sistem Payroll PPPK Paruh Waktu dan diverifikasi secara elektronik.<br>
        Dicetak pada: {{ $printDate }}
    </div>
</body>

</html>