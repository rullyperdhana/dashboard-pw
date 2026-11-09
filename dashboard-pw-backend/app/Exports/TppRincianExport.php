<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TppRincianExport implements FromCollection, WithEvents, WithCustomStartCell
{
    protected $data;
    protected $monthName;
    protected $year;

    public function __construct($data, $monthName, $year)
    {
        $this->data = $data;
        $this->monthName = $monthName;
        $this->year = $year;
    }

    public function collection()
    {
        $collection = collect($this->data);
        
        $totals = [
            'instansi_upt' => 'TOTAL KESELURUHAN',
            'pns_umum' => $collection->sum('pns_umum'),
            'pns_guru' => $collection->sum('pns_guru'),
            'pns_nakes' => $collection->sum('pns_nakes'),
            'pppk_umum' => $collection->sum('pppk_umum'),
            'pppk_guru' => $collection->sum('pppk_guru'),
            'pppk_nakes' => $collection->sum('pppk_nakes'),
            'pppk_pw_umum' => $collection->sum('pppk_pw_umum'),
            'pppk_pw_guru' => $collection->sum('pppk_pw_guru'),
            'pppk_pw_nakes' => $collection->sum('pppk_pw_nakes'),
            'total_pegawai' => $collection->sum('total_pegawai'),
            'rp_pns_umum' => $collection->sum('rp_pns_umum'),
            'rp_pns_guru' => $collection->sum('rp_pns_guru'),
            'rp_pns_nakes' => $collection->sum('rp_pns_nakes'),
            'rp_pppk_umum' => $collection->sum('rp_pppk_umum'),
            'rp_pppk_guru' => $collection->sum('rp_pppk_guru'),
            'rp_pppk_nakes' => $collection->sum('rp_pppk_nakes'),
            'rp_pppk_pw_umum' => $collection->sum('rp_pppk_pw_umum'),
            'rp_pppk_pw_guru' => $collection->sum('rp_pppk_pw_guru'),
            'rp_pppk_pw_nakes' => $collection->sum('rp_pppk_pw_nakes'),
            'rp_total' => $collection->sum('rp_total'),
        ];
        
        $collection->push($totals);
        return $collection;
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Title
                $sheet->mergeCells('A1:U1');
                $sheet->setCellValue('A1', "LAPORAN RINCIAN TPP - BULAN " . strtoupper($this->monthName) . " " . $this->year);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Main Headers
                $sheet->mergeCells('A4:A6');
                $sheet->setCellValue('A4', 'INSTANSI');
                
                $sheet->mergeCells('B4:D4');
                $sheet->setCellValue('B4', 'JUMLAH PNS');
                
                $sheet->mergeCells('E4:G4');
                $sheet->setCellValue('E4', 'JUMLAH PPPK (PENUH WAKTU)');

                $sheet->mergeCells('H4:J4');
                $sheet->setCellValue('H4', 'JUMLAH PPPK (PARUH WAKTU)');
                
                $sheet->mergeCells('K4:K6');
                $sheet->setCellValue('K4', 'JUMLAH PEGAWAI');
                
                $sheet->mergeCells('L4:T4');
                $sheet->setCellValue('L4', 'Jumlah Realisasi TPP & Gaji Bulan ' . $this->monthName . ' (dalam Rupiah)');
                
                $sheet->mergeCells('U4:U6');
                $sheet->setCellValue('U4', 'Total Realisasi Bulan ' . $this->monthName);

                // Sub Headers Row 5 & 6
                // PNS
                $sheet->mergeCells('B5:B6');
                $sheet->setCellValue('B5', 'PNS');
                $sheet->mergeCells('C5:C6');
                $sheet->setCellValue('C5', "PNS GURU DAN\nTENAGA KEPENDIDIKAN");
                $sheet->mergeCells('D5:D6');
                $sheet->setCellValue('D5', "PNS TENAGA\nKESEHATAN");
                
                // PPPK
                $sheet->mergeCells('E5:E6');
                $sheet->setCellValue('E5', "PPPK PENUH\nWAKTU");
                $sheet->mergeCells('F5:F6');
                $sheet->setCellValue('F5', "PPPK PENUH WAKTU\nGURU DAN TENAGA\nKEPENDIDIKAN");
                $sheet->mergeCells('G5:G6');
                $sheet->setCellValue('G5', "PPPK PENUH WAKTU\nTENAGA KESEHATAN");

                // PPPK PW
                $sheet->mergeCells('H5:H6');
                $sheet->setCellValue('H5', "PPPK PARUH\nWAKTU");
                $sheet->mergeCells('I5:I6');
                $sheet->setCellValue('I5', "PPPK PARUH WAKTU\nGURU DAN TENAGA\nKEPENDIDIKAN");
                $sheet->mergeCells('J5:J6');
                $sheet->setCellValue('J5', "PPPK PARUH WAKTU\nTENAGA KESEHATAN");

                // Realisasi PNS
                $sheet->mergeCells('L5:L6');
                $sheet->setCellValue('L5', 'PNS');
                $sheet->mergeCells('M5:M6');
                $sheet->setCellValue('M5', "PNS GURU DAN\nTENAGA KEPENDIDIKAN");
                $sheet->mergeCells('N5:N6');
                $sheet->setCellValue('N5', "PNS TENAGA\nKESEHATAN");

                // Realisasi PPPK
                $sheet->mergeCells('O5:O6');
                $sheet->setCellValue('O5', "PPPK PENUH\nWAKTU");
                $sheet->mergeCells('P5:P6');
                $sheet->setCellValue('P5', "PPPK PENUH WAKTU\nGURU DAN TENAGA\nKEPENDIDIKAN");
                $sheet->mergeCells('Q5:Q6');
                $sheet->setCellValue('Q5', "PPPK PENUH WAKTU\nTENAGA KESEHATAN");

                // Realisasi PPPK PW
                $sheet->mergeCells('R5:R6');
                $sheet->setCellValue('R5', "PPPK PARUH\nWAKTU");
                $sheet->mergeCells('S5:S6');
                $sheet->setCellValue('S5', "PPPK PARUH WAKTU\nGURU DAN TENAGA\nKEPENDIDIKAN");
                $sheet->mergeCells('T5:T6');
                $sheet->setCellValue('T5', "PPPK PARUH WAKTU\nTENAGA KESEHATAN");
                
                // Numbering Row 7
                $cols = [
                    'B'=>'1', 'C'=>'2', 'D'=>'3', 
                    'E'=>'4', 'F'=>'5', 'G'=>'6', 
                    'H'=>'7', 'I'=>'8', 'J'=>'9', 
                    'K'=>'10', 
                    'L'=>'11', 'M'=>'12', 'N'=>'13', 
                    'O'=>'14', 'P'=>'15', 'Q'=>'16', 
                    'R'=>'17', 'S'=>'18', 'T'=>'19', 
                    'U'=>'20'
                ];
                foreach($cols as $col => $val) {
                    $sheet->setCellValue($col.'7', $val);
                }

                // Apply styles to headers
                $headerRange = 'A4:U7';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true);
                
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ];
                $sheet->getStyle($headerRange)->applyFromArray($styleArray);

                // Colors
                $sheet->getStyle('A4:A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF5B9BD5'); // Blue Instansi
                $sheet->getStyle('B4:J4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00'); // Yellow Top PNS/PPPK/PW
                $sheet->getStyle('K4:K7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFF00'); // Yellow Total Pegawai
                $sheet->getStyle('B5:J7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFE699'); // Light Yellow
                $sheet->getStyle('L4:U4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF92D050'); // Green Top Realisasi
                $sheet->getStyle('L5:T7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2EFDA'); // Light Green
                $sheet->getStyle('U5:U7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF92D050'); // Green Total Realisasi
                
                // Set Column Widths
                $sheet->getColumnDimension('A')->setWidth(40);
                foreach (range('B', 'U') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(20);
                }

                // Format numbers & currency for data rows
                $lastRow = 7 + count($this->data) + 1; // +1 for the TOTAL row
                $dataRange = 'A8:U' . $lastRow;
                $sheet->getStyle($dataRange)->applyFromArray($styleArray);
                
                // Currency columns
                $sheet->getStyle('L8:U' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

                // Bold the total row
                $sheet->getStyle('A' . $lastRow . ':U' . $lastRow)->getFont()->setBold(true);
                // Center align the text in column A for Total
                $sheet->getStyle('A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        ];
    }
}
