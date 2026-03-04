<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RefEselonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['kd_eselon' => '00', 'rp_eselon' => 0, 'uraian' => 'NON ESELON', 'bup' => 58],
            ['kd_eselon' => '1A', 'rp_eselon' => 5500000, 'uraian' => 'ESELON 1A', 'bup' => 58],
            ['kd_eselon' => '1B', 'rp_eselon' => 4375000, 'uraian' => 'ESELON 1B', 'bup' => 58],
            ['kd_eselon' => '1C', 'rp_eselon' => 3780000, 'uraian' => 'ESELON 1C', 'bup' => 58],
            ['kd_eselon' => '1D', 'rp_eselon' => 3240000, 'uraian' => 'ESELON 1D', 'bup' => 58],
            ['kd_eselon' => '2A', 'rp_eselon' => 3250000, 'uraian' => 'ESELON 2A', 'bup' => 60],
            ['kd_eselon' => '2B', 'rp_eselon' => 2025000, 'uraian' => 'ESELON 2B', 'bup' => 60],
            ['kd_eselon' => '3A', 'rp_eselon' => 1260000, 'uraian' => 'ESELON 3A', 'bup' => 58],
            ['kd_eselon' => '3B', 'rp_eselon' => 980000, 'uraian' => 'ESELON 3B', 'bup' => 58],
            ['kd_eselon' => '4A', 'rp_eselon' => 540000, 'uraian' => 'ESELON 4A', 'bup' => 58],
            ['kd_eselon' => '4B', 'rp_eselon' => 490000, 'uraian' => 'ESELON 4B', 'bup' => 58],
            ['kd_eselon' => '5A', 'rp_eselon' => 360000, 'uraian' => 'ESELON 5A', 'bup' => 58],
            ['kd_eselon' => '5B', 'rp_eselon' => 240000, 'uraian' => 'ESELON 5B', 'bup' => 58],
            ['kd_eselon' => '01', 'rp_eselon' => 5400000, 'uraian' => 'GUBERNUR', 'bup' => 58],
            ['kd_eselon' => '02', 'rp_eselon' => 4320000, 'uraian' => 'WAKIL GUBERNUR', 'bup' => 58],
            ['kd_eselon' => '03', 'rp_eselon' => 185000, 'uraian' => 'NON ESELON GOLONGAN III', 'bup' => 58],
        ];

        foreach ($data as $item) {
            \App\Models\RefEselon::updateOrCreate(
                ['kd_eselon' => $item['kd_eselon']],
                $item
            );
        }
    }
}
