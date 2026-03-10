<?php

namespace App\Exports;

use App\Models\MasterPegawai;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\DB;

class EmployeesExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithMapping, WithColumnFormatting
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = MasterPegawai::query()
            ->leftJoin('satkers', function ($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->leftJoin('ref_stapeg', 'master_pegawai.kdstapeg', '=', 'ref_stapeg.kdstapeg')
            ->leftJoin('ref_jabatan_fungsional', 'master_pegawai.kdfungsi', '=', 'ref_jabatan_fungsional.kdfungsi')
            ->leftJoin('ref_eselon', 'master_pegawai.kdeselon', '=', 'ref_eselon.kd_eselon')
            ->select(
                'master_pegawai.*',
                'satkers.nmskpd',
                'ref_stapeg.nmstapeg',
                DB::raw('CASE WHEN master_pegawai.kdfungsi = "00000" THEN ref_eselon.uraian ELSE ref_jabatan_fungsional.nama_jabatan END as nama_jabatan')
            );

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('master_pegawai.nip', 'like', "%$s%")
                    ->orWhere('master_pegawai.nama', 'like', "%$s%");
            });
        }

        if (!empty($this->filters['kdskpd'])) {
            $query->where('master_pegawai.kdskpd', $this->filters['kdskpd']);
        }

        if (!empty($this->filters['kd_jns_peg'])) {
            $query->where('master_pegawai.kd_jns_peg', $this->filters['kd_jns_peg']);
        }

        if (!empty($this->filters['kdstapeg'])) {
            $query->where('master_pegawai.kdstapeg', $this->filters['kdstapeg']);
        }

        return $query->orderBy('satkers.nmskpd')->orderBy('master_pegawai.nama')->get();
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA',
            'NIK',
            'NPWP',
            'TIPE PEG.',
            'STATUS PEG.',
            'SKPD',
            'JABATAN',
            'ESELON',
            'GOL.',
            'TUNJ. ESELON',
            'TUNJ. FUNGSI',
            'TGL LAHIR',
            'JENIS KELAMIN',
            'ALAMAT',
            'WAKTU EXPORT'
        ];
    }

    public function map($row): array
    {
        $tipePeg = $row->kd_jns_peg == 4 ? 'PPPK' : 'PNS';
        $jk = $row->kdjenkel == 1 ? 'Laki-laki' : 'Perempuan';

        return [
            $row->nip . " ", // Add space to force string
            $row->nama,
            $row->noktp . " ",
            $row->npwp . " ",
            $tipePeg,
            $row->nmstapeg,
            $row->nmskpd,
            $row->nama_jabatan,
            $row->kdeselon,
            $row->kdgolo,
            $row->tjeselon,
            $row->tjfungsi,
            $row->tgllhr,
            $jk,
            $row->alamat,
            date('Y-m-d H:i:s')
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
            ],
        ];
    }
}
