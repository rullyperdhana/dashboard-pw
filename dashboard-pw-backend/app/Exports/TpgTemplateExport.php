<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class TpgTemplateExport implements FromCollection, WithHeadings, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return new Collection([
            [
                '123456789012345678',
                'CONTOH NAMA GURU 1',
                '1234567890',
                'SD NEGERI 1 CONTOH',
                4500000,
                225000,
                45000,
                4230000
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA',
            'NO_REKENING',
            'SATDIK',
            'SALUR_BRUT',
            'PPH',
            'POT_JKN',
            'SALUR_NETT'
        ];
    }

    public function title(): string
    {
        return 'Template Upload TPG';
    }
}
