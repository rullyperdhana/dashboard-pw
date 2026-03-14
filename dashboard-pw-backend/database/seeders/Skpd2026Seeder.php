<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Skpd2026Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/skpd_2026_parsed.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("File $jsonPath tidak ditemukan!");
            return;
        }

        $jsonData = file_get_contents($jsonPath);
        $records = json_decode($jsonData, true);

        $now = now();
        $insertData = [];

        foreach ($records as $record) {
            $insertData[] = [
                'kode_skpd' => $record['kode_skpd'],
                'nama_skpd' => $record['nama_skpd'],
                'is_skpd' => $record['is_skpd'],
                'kode_simgaji' => null,
                'kode_sipd' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks to avoid overwhelming the database
        foreach (array_chunk($insertData, 50) as $chunk) {
            DB::table('skpd_2026')->insert($chunk);
        }

        $this->command->info("Berhasil menambahkan " . count($records) . " data SKPD 2026.");
    }
}
