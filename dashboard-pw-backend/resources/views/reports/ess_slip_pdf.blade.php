<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $data->nama }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; line-height: 1.4; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #666; }
        
        .info-section { margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .label { width: 100px; color: #666; }
        .value { font-weight: bold; }

        .main-content { width: 100%; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; }
        .column { width: 50%; vertical-align: top; padding: 10px; }
        .column-left { border-right: 1px solid #eee; }
        
        .section-title { font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; color: #2c3e50; }
        
        .data-table { width: 100%; }
        .data-table td { padding: 3px 0; }
        .amount { text-align: right; font-family: 'Courier New', Courier, monospace; }

        .footer { margin-top: 20px; }
        .summary-box { background: #f9f9f9; padding: 10px; border: 1px solid #eee; margin-bottom: 20px; }
        .summary-row { font-size: 12px; font-weight: bold; margin-bottom: 5px; }
        .summary-label { display: inline-block; width: 150px; }
        
        .signature-section { margin-top: 30px; width: 100%; }
        .signature-box { width: 220px; text-align: center; float: right; }
        .qr-placeholder { margin: 10px auto; width: 80px; height: 80px; border: 1px solid #ddd; padding: 5px; }
        
        .watermark { position: absolute; top: 30%; left: 10%; font-size: 80px; color: rgba(0,0,0,0.03); transform: rotate(-45deg); z-index: -1; pointer-events: none; }
        .text-success { color: #27ae60; }
        .text-error { color: #c0392b; }
    </style>
</head>
<body>
    <div class="watermark">SIP-GAJI OFFICIAL</div>

    <div class="header">
        <h1>PEMERINTAH PROVINSI KALIMANTAN SELATAN</h1>
        <p>SLIP GAJI PEGAWAI - {{ strtoupper($data->jenis_gaji) }}</p>
        <p>Periode: {{ $bulan_nama }} {{ $data->tahun }}</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Nama</td><td class="value">: {{ $data->nama }}</td>
                <td class="label">NIP</td><td class="value">: {{ $data->nip }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td><td class="value">: {{ $data->jabatan }}</td>
                <td class="label">Golongan</td><td class="value">: {{ $data->golongan }} ({{ $data->kdpangkat ?? '-' }})</td>
            </tr>
            <tr>
                <td class="label">Unit Kerja</td><td class="value" colspan="3">: {{ $data->skpd }}</td>
            </tr>
        </table>
    </div>

    <table class="main-content">
        <tr>
            <td class="column column-left">
                <div class="section-title">Penghasilan (Income)</div>
                <table class="data-table">
                    <tr><td>Gaji Pokok</td><td class="amount">{{ number_format($data->gaji_pokok, 0, ',', '.') }}</td></tr>
                    @if($data->tunj_istri > 0) <tr><td>Tunj. Istri / Suami</td><td class="amount">{{ number_format($data->tunj_istri, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_anak > 0) <tr><td>Tunj. Anak</td><td class="amount">{{ number_format($data->tunj_anak, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_eselon > 0) <tr><td>Tunj. Eselon</td><td class="amount">{{ number_format($data->tunj_eselon, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_struktural > 0) <tr><td>Tunj. Struktural</td><td class="amount">{{ number_format($data->tunj_struktural, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_fungsional > 0) <tr><td>Tunj. Fungsional</td><td class="amount">{{ number_format($data->tunj_fungsional, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_umum > 0) <tr><td>Tunj. Umum</td><td class="amount">{{ number_format($data->tunj_umum, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_beras > 0) <tr><td>Tunj. Beras</td><td class="amount">{{ number_format($data->tunj_beras, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_pph > 0) <tr><td>Tunj. PPh</td><td class="amount">{{ number_format($data->tunj_pph, 0, ',', '.') }}</td></tr> @endif
                    @if($data->tunj_tpp > 0) <tr><td>Tambahan TPP</td><td class="amount">{{ number_format($data->tunj_tpp, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pembulatan > 0) <tr><td>Pembulatan</td><td class="amount">{{ number_format($data->pembulatan, 0, ',', '.') }}</td></tr> @endif
                </table>
            </td>
            <td class="column">
                <div class="section-title">Potongan (Deductions)</div>
                <table class="data-table text-error">
                    @if($data->pot_iwp > 0) <tr><td>IWP (1%)</td><td class="amount">{{ number_format($data->pot_iwp, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_iwp1 > 0) <tr><td>IWP (2%)</td><td class="amount">{{ number_format($data->pot_iwp1, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_iwp8 > 0) <tr><td>IWP (8%)</td><td class="amount">{{ number_format($data->pot_iwp8, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_askes > 0) <tr><td>BPJS Kesehatan / Askes</td><td class="amount">{{ number_format($data->pot_askes, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_pph > 0) <tr><td>PPh 21</td><td class="amount">{{ number_format($data->pot_pph, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_taperum > 0) <tr><td>Taperum</td><td class="amount">{{ number_format($data->pot_taperum, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_jkk > 0) <tr><td>JKK</td><td class="amount">{{ number_format($data->pot_jkk, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_jkm > 0) <tr><td>JKM</td><td class="amount">{{ number_format($data->pot_jkm, 0, ',', '.') }}</td></tr> @endif
                    @if($data->pot_koperasi > 0) <tr><td>Koperasi</td><td class="amount">{{ number_format($data->pot_koperasi, 0, ',', '.') }}</td></tr> @endif
                    @if(($data->total_potongan - ($data->pot_iwp+$data->pot_iwp1+$data->pot_iwp8+$data->pot_askes+$data->pot_pph+$data->pot_taperum+$data->pot_jkk+$data->pot_jkm+$data->pot_koperasi)) > 0)
                        <tr><td>Potongan Lainnya</td><td class="amount">{{ number_format($data->total_potongan - ($data->pot_iwp+$data->pot_iwp1+$data->pot_iwp8+$data->pot_askes+$data->pot_pph+$data->pot_taperum+$data->pot_jkk+$data->pot_jkm+$data->pot_koperasi), 0, ',', '.') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <div class="summary-box">
            <div class="summary-row"><span class="summary-label">Total Penghasilan (Kotor) :</span> Rp {{ number_format($data->kotor, 0, ',', '.') }}</div>
            <div class="summary-row text-error"><span class="summary-label">Total Potongan :</span> Rp {{ number_format($data->total_potongan, 0, ',', '.') }}</div>
            <div class="summary-row text-success" style="font-size: 14px; margin-top: 5px;"><span class="summary-label">Take Home Pay (Bersih) :</span> Rp {{ number_format($data->bersih, 0, ',', '.') }}</div>
        </div>

        <div class="signature-section">
            <p style="font-style: italic; color: #666;">Dicetak otomatis melalui Sistem Informasi Penggajian (SIP-Gaji) pada {{ date('d/m/Y H:i:s') }}.</p>
            
            <div class="signature-box">
                <div>Dokumen Sah dan Valid</div>
                <div class="qr-placeholder">
                    <img src="data:image/png;base64,{{ $qrcode }}" style="width: 100%; height: 100%;">
                </div>
                <div style="font-weight: bold;">SIP-GAJI OFFICIAL</div>
                <div style="font-size: 9px; color: #888;">ID: {{ strtoupper(substr(md5($data->nip . $data->id . $data->updated_at), 0, 16)) }}</div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>
