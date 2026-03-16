<?php

namespace App\Imports;

use App\Models\Sp2dRealization;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class Sp2dPotonganImport implements ToCollection
{
    protected $isPreview;
    public $previewData = [];

    public function __construct($isPreview = false)
    {
        $this->isPreview = $isPreview;
    }

    public function collection(Collection $rows)
    {
        $headerIndex = -1;
        $colSp2d = -1;
        $colNilai = -1;

        // 1. Find Header Row
        foreach ($rows as $idx => $row) {
            $rowValues = $row->map(fn($v) => strtoupper(trim((string)$v)))->toArray();
            
            $foundSp2d = false;
            $foundNilai = false;

            foreach ($rowValues as $colIdx => $val) {
                if (str_contains($val, 'NOMOR SP2D') || str_contains($val, 'NO. SP2D') || $val === 'NO SP2D') {
                    $colSp2d = $colIdx;
                    $foundSp2d = true;
                }
                if ($val === 'NILAI' || str_contains($val, 'JUMLAH')) {
                    $colNilai = $colIdx;
                    $foundNilai = true;
                }
            }

            if ($foundSp2d && $foundNilai) {
                $headerIndex = $idx;
                break;
            }
        }

        // Fallback for the specific file structure observed if header scan fails
        if ($headerIndex === -1) {
            $colSp2d = 3; // Column D (0-indexed is 3)
            $colNilai = 10; // Column K (0-indexed is 10)
            $headerIndex = 0; // Assume data starts after row 0 if no header found
        }

        // 2. Aggregate Data
        $aggregates = [];
        foreach ($rows as $idx => $row) {
            if ($idx <= $headerIndex) continue;

            $nomorSp2d = trim((string)($row[$colSp2d] ?? ''));
            if (empty($nomorSp2d)) continue;

            $nilai = $this->cleanNum($row[$colNilai] ?? 0);
            if ($nilai == 0) continue;

            if (!isset($aggregates[$nomorSp2d])) {
                $aggregates[$nomorSp2d] = 0;
            }
            $aggregates[$nomorSp2d] += $nilai;
        }

        // 3. Process Aggregates
        foreach ($aggregates as $nomorSp2d => $totalPotongan) {
            if ($this->isPreview) {
                $exists = Sp2dRealization::where('nomor_sp2d', $nomorSp2d)->first();
                $this->previewData[] = [
                    'nomor_sp2d' => $nomorSp2d,
                    'potongan' => $totalPotongan,
                    'exists' => (bool)$exists,
                    'current_potongan' => $exists ? $exists->potongan : 0,
                    'skpd_sipd' => $exists ? $exists->nama_skpd_sipd : 'Tidak ditemukan',
                ];
            } else {
                $records = Sp2dRealization::where('nomor_sp2d', $nomorSp2d)->get();
                /** @var Sp2dRealization $record */
                foreach ($records as $record) {
                    $record->potongan = $totalPotongan;
                    $record->netto = $record->brutto - $totalPotongan;
                    $record->save();
                }
            }
        }
    }

    private function cleanNum($val)
    {
        if (is_numeric($val)) return (float) $val;
        if (is_string($val)) {
            $val = preg_replace('/[^\d,.]/', '', $val);
            if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (strpos($val, ',') !== false) {
                if (preg_match('/,\d{3}$/', $val)) $val = str_replace(',', '', $val);
                else $val = str_replace(',', '.', $val);
            } elseif (strpos($val, '.') !== false && substr_count($val, '.') > 1) {
                $val = str_replace('.', '', $val);
            }
            return (float) $val;
        }
        return 0;
    }
}
