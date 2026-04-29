<?php

namespace App\Exports;

use App\Models\PaymentDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PppkPwMonthlyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $month;
    protected $year;
    protected $idskpd;
    protected $rka_id;
    protected $rowNumber = 0;

    public function __construct($month, $year, $idskpd, $rka_id)
    {
        $this->month = $month;
        $this->year = $year;
        $this->idskpd = $idskpd;
        $this->rka_id = $rka_id;
    }

    public function collection()
    {
        $query = PaymentDetail::select(
            'tb_payment_detail.*',
            'rs.nama_sub_giat',
            'pw.nip',
            'pw.nama',
            'pw.jabatan',
            'pw.skpd'
        )
        ->join('tb_payment as p', 'tb_payment_detail.payment_id', '=', 'p.id')
        ->leftJoin('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
        ->join('pegawai_pw as pw', 'tb_payment_detail.employee_id', '=', 'pw.id');

        if ($this->month) $query->where('p.month', $this->month);
        if ($this->year) $query->where('p.year', $this->year);
        if ($this->idskpd) $query->where('pw.skpd', $this->idskpd);
        if ($this->rka_id) $query->where('p.rka_id', $this->rka_id);

        return $query->orderBy('pw.nama')->get();
    }

    public function headings(): array
    {
        return [
            'NO',
            'NIP',
            'NAMA PEGAWAI',
            'JABATAN',
            'SKPD',
            'SUB KEGIATAN',
            'SUMBER DANA',
            'GAJI POKOK',
            'PAJAK',
            'IWP',
            'POT. LAIN',
            'BERSIH DITERIMA'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            " " . $row->nip, // Force string
            $row->nama,
            $row->jabatan,
            $row->skpd,
            $row->nama_sub_giat ?? '-',
            $row->sumber_dana ?? 'APBD',
            (float) $row->gaji_pokok,
            (float) $row->pajak,
            (float) $row->iwp,
            (float) $row->potongan,
            (float) $row->total_amoun,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
