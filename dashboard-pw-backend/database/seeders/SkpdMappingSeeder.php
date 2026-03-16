<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkpdMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            ["source_name" => "Unknown", "source_code" => "001", "skpd_id" => 1, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "002", "skpd_id" => 6, "skpd_2026_id" => 6, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "006", "skpd_id" => 16, "skpd_2026_id" => 16, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "007", "skpd_id" => 96, "skpd_2026_id" => 96, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "008", "skpd_id" => 115, "skpd_2026_id" => 115, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "009", "skpd_id" => 42, "skpd_2026_id" => 42, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "010", "skpd_id" => 37, "skpd_2026_id" => 37, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "011", "skpd_id" => 79, "skpd_2026_id" => 79, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "012", "skpd_id" => 35, "skpd_2026_id" => 35, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "013", "skpd_id" => 59, "skpd_2026_id" => 59, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "014", "skpd_id" => 22, "skpd_2026_id" => 22, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "015", "skpd_id" => 45, "skpd_2026_id" => 45, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "016", "skpd_id" => 47, "skpd_2026_id" => 47, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "017", "skpd_id" => 58, "skpd_2026_id" => 58, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "018", "skpd_id" => 48, "skpd_2026_id" => 48, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "019", "skpd_id" => 119, "skpd_2026_id" => 119, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "020", "skpd_id" => 50, "skpd_2026_id" => 50, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "021", "skpd_id" => 62, "skpd_2026_id" => 62, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "022", "skpd_id" => 67, "skpd_2026_id" => 67, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "023", "skpd_id" => 21, "skpd_2026_id" => 21, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "024", "skpd_id" => 81, "skpd_2026_id" => 81, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "025", "skpd_id" => 97, "skpd_2026_id" => 97, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "026", "skpd_id" => 41, "skpd_2026_id" => 41, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "027", "skpd_id" => 85, "skpd_2026_id" => 85, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "029", "skpd_id" => 95, "skpd_2026_id" => 95, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "030", "skpd_id" => 20, "skpd_2026_id" => 20, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "031", "skpd_id" => 118, "skpd_2026_id" => 118, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "032", "skpd_id" => 49, "skpd_2026_id" => 49, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "033", "skpd_id" => 98, "skpd_2026_id" => 98, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "034", "skpd_id" => 28, "skpd_2026_id" => 28, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "035", "skpd_id" => 114, "skpd_2026_id" => 114, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "036", "skpd_id" => 113, "skpd_2026_id" => 113, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "037", "skpd_id" => 117, "skpd_2026_id" => 117, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "100", "skpd_id" => 19, "skpd_2026_id" => 19, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "101", "skpd_id" => 40, "skpd_2026_id" => 40, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "102", "skpd_id" => 44, "skpd_2026_id" => 44, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "103", "skpd_id" => 83, "skpd_2026_id" => 83, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TANAH LAUT", "source_code" => "070", "skpd_id" => 126, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK KOTABARU", "source_code" => "071", "skpd_id" => 127, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJAR", "source_code" => "072", "skpd_id" => 128, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BARITO KUALA", "source_code" => "073", "skpd_id" => 129, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TAPIN", "source_code" => "074", "skpd_id" => 130, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI SELATAN", "source_code" => "075", "skpd_id" => 131, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI TENGAH", "source_code" => "076", "skpd_id" => 132, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI UTARA", "source_code" => "077", "skpd_id" => 133, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TABALONG", "source_code" => "078", "skpd_id" => 134, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TANAH BUMBU", "source_code" => "079", "skpd_id" => 135, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BALANGAN", "source_code" => "080", "skpd_id" => 136, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJARMASIN", "source_code" => "081", "skpd_id" => 137, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJARBARU", "source_code" => "082", "skpd_id" => 138, "skpd_2026_id" => 1, "type" => "all"],
        ];

        foreach ($mappings as $mapping) {
            \App\Models\SkpdMapping::updateOrCreate(
                ['source_code' => $mapping['source_code'], 'source_name' => $mapping['source_name']],
                $mapping
            );
        }
    }
}
