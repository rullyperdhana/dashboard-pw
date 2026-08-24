<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithLimit;
use Illuminate\Support\Collection;

class GenericImport implements ToCollection, WithLimit
{
    public function collection(Collection $rows)
    {
        return $rows;
    }

    public function limit(): int
    {
        return 10;
    }
}
