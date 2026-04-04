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
            [
                'key' => 'thr_pppk_pw_method',
                'value' => 'proporsional',
                'description' => 'Metode Perhitungan THR PPPK-PW',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'thr_pppk_pw_amount',
                'value' => '600000',
                'description' => 'Nominal THR PPPK-PW (Bernilai Tetap)',
                'type' => 'float',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'thr_pppk_pw_multiplier',
                'value' => '2',
                'description' => 'Multiplier THR PPPK-PW (n/12)',
                'type' => 'integer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gaji13_pppk_pw_method',
                'value' => 'proporsional',
                'description' => 'Metode Perhitungan Gaji 13 PPPK-PW',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gaji13_pppk_pw_amount',
                'value' => '600000',
                'description' => 'Nominal Gaji 13 PPPK-PW (Bernilai Tetap)',
                'type' => 'float',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'gaji13_pppk_pw_multiplier',
                'value' => '2',
                'description' => 'Multiplier Gaji 13 PPPK-PW (n/12)',
                'type' => 'integer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
