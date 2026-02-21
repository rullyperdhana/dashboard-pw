<?php

namespace App\Exports;

use App\Models\TpgData;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TpgExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected $tahun;
    protected $triwulan;

    public function __construct($tahun, $triwulan = null)
    {
        $this->tahun = $tahun;
        $this->triwulan = $triwulan;
    }

    public function query()
    {
        $query = TpgData::where('tahun', $this->tahun);

        if ($this->triwulan) {
            $query->where('triwulan', $this->triwulan);
        }

        return $query->orderBy('satdik')->orderBy('nama');
    }

    public function headings(): array
    {
        return [
            'NO',
            'NIP',
            'NAMA',
            'NO. REKENING BANK',
            'SATDIK',
            'TRIWULAN',
            'JENIS',
            'TAHUN',
            'SALUR BRUT',
            'PPH',
            'POT. JKN',
            'SALUR NETT',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            "'" . $row->nip,
            $row->nama,
            $row->no_rekening,
            $row->satdik,
            'TW ' . $row->triwulan,
            $row->jenis,
            $row->tahun,
            $row->salur_brut,
            $row->pph,
            $row->pot_jkn,
            $row->salur_nett,
        ];
    }

    public function title(): string
    {
        $title = "TPG {$this->tahun}";
        if ($this->triwulan) {
            $title .= " TW{$this->triwulan}";
        }
        return $title;
    }
}
