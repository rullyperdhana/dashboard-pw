<?php

namespace App\Exports;

use App\Models\GajiPppk;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\DB;

class GajiPppkExport implements FromQuery, WithHeadings, WithStyles, ShouldAutoSize, WithMapping, WithColumnFormatting
{
    protected $filters;
    protected $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = GajiPppk::query()
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pppk.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pppk.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pppk.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pppk', 'all')) as sm"), 'gaji_pppk.kdskpd', '=', 'sm.source_code')
            ->select(
                'gaji_pppk.*',
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pppk.satker, gaji_pppk.skpd) as skpd_display')
            );

        // Filter by period
        if (!empty($this->filters['bulan']) && !empty($this->filters['tahun'])) {
            $query->where('gaji_pppk.bulan', $this->filters['bulan'])
                ->where('gaji_pppk.tahun', $this->filters['tahun']);
        } elseif (!empty($this->filters['tahun'])) {
            $query->where('gaji_pppk.tahun', $this->filters['tahun']);
        }

        // Filter by SKPD
        if (!empty($this->filters['kdskpd'])) {
            $query->where('gaji_pppk.kdskpd', $this->filters['kdskpd']);
        }

        // Filter by jenis gaji
        if (!empty($this->filters['jenis_gaji'])) {
            $query->where('gaji_pppk.jenis_gaji', $this->filters['jenis_gaji']);
        }

        // Search
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('gaji_pppk.nama', 'like', "%{$search}%")
                    ->orWhere('gaji_pppk.nip', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('gaji_pppk.skpd')->orderBy('gaji_pppk.nama');
    }

    public function headings(): array
    {
        return [
            'NO',
            'NIP',
            'NAMA',
            'GOLONGAN',
            'JABATAN',
            'SKPD',
            'BULAN',
            'TAHUN',
            'JENIS GAJI',
            'GAJI POKOK',
            'TUNJ. ISTRI',
            'TUNJ. ANAK',
            'TUNJ. FUNGSIONAL',
            'TUNJ. STRUKTURAL',
            'TUNJ. UMUM',
            'TUNJ. BERAS',
            'TUNJ. PPH',
            'TUNJ. TPP',
            'PENGHASILAN KOTOR',
            'POT. IWP',
            'POT. ASKES',
            'POT. PPH',
            'POT. TAPERUM',
            'POT. JKK',
            'POT. JKM',
            'TOTAL POTONGAN',
            'PENGHASILAN BERSIH',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->nip . " ",
            $row->nama,
            $row->golongan,
            $row->jabatan,
            $row->skpd_display ?? $row->skpd,
            $row->bulan,
            $row->tahun,
            $row->jenis_gaji,
            $row->gaji_pokok ?? 0,
            $row->tunj_istri ?? 0,
            $row->tunj_anak ?? 0,
            $row->tunj_fungsional ?? 0,
            $row->tunj_struktural ?? 0,
            $row->tunj_umum ?? 0,
            $row->tunj_beras ?? 0,
            $row->tunj_pph ?? 0,
            $row->tunj_tpp ?? 0,
            $row->kotor ?? 0,
            $row->pot_iwp ?? 0,
            $row->pot_askes ?? 0,
            $row->pot_pph ?? 0,
            $row->pot_taperum ?? 0,
            $row->pot_jkk ?? 0,
            $row->pot_jkm ?? 0,
            $row->total_potongan ?? 0,
            $row->bersih ?? 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT, // NIP as text
            'J' => '#,##0',
            'K' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
            'N' => '#,##0',
            'O' => '#,##0',
            'P' => '#,##0',
            'Q' => '#,##0',
            'R' => '#,##0',
            'S' => '#,##0',
            'T' => '#,##0',
            'U' => '#,##0',
            'V' => '#,##0',
            'W' => '#,##0',
            'X' => '#,##0',
            'Y' => '#,##0',
            'Z' => '#,##0',
            'AA' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'] // Teal to match PPPK page theme
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
