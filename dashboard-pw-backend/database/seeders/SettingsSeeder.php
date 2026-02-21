<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('settings')->insert([
            [
                'key' => 'pppk_jkk_percentage',
                'value' => '0.24',
                'description' => 'Persentase Jaminan Kecelakaan Kerja PPPK',
                'type' => 'float',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pppk_jkm_percentage',
                'value' => '0.72',
                'description' => 'Persentase Jaminan Kematian PPPK',
                'type' => 'float',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
