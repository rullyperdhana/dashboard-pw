<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PPh21Service
{
    /**
     * Map Simgaji columns to PTKP Status
     * kdstawin: 1=TK, 2=K
     * janak: number of children
     */
    public function getPTKPStatus($kdstawin, $janak)
    {
        $status = ($kdstawin == 2) ? 'K' : 'TK';
        $tanggungan = min((int)$janak, 3);
        return $status . '/' . $tanggungan;
    }

    /**
     * Get PTKP Amount (2024)
     */
    public function getPTKPAmount($status)
    {
        $ptkpTable = [
            'TK/0' => 54000000, 'TK/1' => 58500000, 'TK/2' => 63000000, 'TK/3' => 67500000,
            'K/0' => 58500000, 'K/1' => 63000000, 'K/2' => 67500000, 'K/3' => 72000000,
        ];
        return $ptkpTable[$status] ?? 54000000;
    }

    /**
     * Get TER Category based on PTKP Status
     */
    public function getTERCategory($status)
    {
        $catA = ['TK/0', 'TK/1', 'K/0'];
        $catB = ['TK/2', 'TK/3', 'K/1', 'K/2'];
        $catC = ['K/3'];

        if (in_array($status, $catA)) return 'A';
        if (in_array($status, $catB)) return 'B';
        if (in_array($status, $catC)) return 'C';
        
        return 'A'; // Fallback
    }

    /**
     * Calculate Monthly PPh 21 using TER
     */
    public function calculateMonthlyTER($grossIncome, $category)
    {
        $rateRow = DB::table('pph21_ter_rates')
            ->where('category', $category)
            ->where('min_gross', '<=', $grossIncome)
            ->where(function($q) use ($grossIncome) {
                $q->where('max_gross', '>', $grossIncome)
                  ->orWhereNull('max_gross');
            })
            ->first();

        $rate = $rateRow ? $rateRow->rate : 0;
        return round(($grossIncome * $rate) / 100);
    }

    /**
     * Calculate Annual PPh 21 using Pasal 17
     */
    public function calculateAnnualPasal17($taxableIncome)
    {
        $tax = 0;
        $remaining = max(0, $taxableIncome);

        // Brackets 2024 (Tarif Progresif UU HPP)
        $brackets = [
            [60000000, 0.05],
            [190000000, 0.15], // 60M to 250M (diff 190M)
            [250000000, 0.25], // 250M to 500M (diff 250M)
            [4500000000, 0.30], // 500M to 5M (diff 4.5B)
            [null, 0.35]
        ];

        foreach ($brackets as $bracket) {
            $limit = $bracket[0];
            $rate = $bracket[1];

            if ($limit === null || $remaining <= $limit) {
                $tax += $remaining * $rate;
                break;
            } else {
                $tax += $limit * $rate;
                $remaining -= $limit;
            }
        }

        return round($tax);
    }
}
