<?php

namespace Database\Seeders;

use App\Models\ApiFieldConfig;
use Illuminate\Database\Seeder;

class ApiFieldConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // listinstansi
            ['endpoint' => 'listinstansi', 'field_key' => 'kode_instansi', 'field_label' => 'Kode Instansi', 'source_table' => 'satkers', 'sort_order' => 1],
            ['endpoint' => 'listinstansi', 'field_key' => 'nama_instansi', 'field_label' => 'Nama Instansi', 'source_table' => 'satkers', 'sort_order' => 2],

            // listpegawai
            ['endpoint' => 'listpegawai', 'field_key' => 'nik', 'field_label' => 'NIK', 'source_table' => 'master_pegawai', 'sort_order' => 1],
            ['endpoint' => 'listpegawai', 'field_key' => 'nip', 'field_label' => 'NIP', 'source_table' => 'master_pegawai', 'sort_order' => 2],
            ['endpoint' => 'listpegawai', 'field_key' => 'nama', 'field_label' => 'Nama Pegawai', 'source_table' => 'master_pegawai', 'sort_order' => 3],
            ['endpoint' => 'listpegawai', 'field_key' => 'npwp', 'field_label' => 'NPWP', 'source_table' => 'master_pegawai', 'sort_order' => 4],
            ['endpoint' => 'listpegawai', 'field_key' => 'tanggal_lahir', 'field_label' => 'Tanggal Lahir', 'source_table' => 'master_pegawai', 'sort_order' => 5],

            // listgaji
            ['endpoint' => 'listgaji', 'field_key' => 'periode', 'field_label' => 'Periode', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 1],
            ['endpoint' => 'listgaji', 'field_key' => 'sertifikat_fasilitas', 'field_label' => 'Sertifikat Fasilitas', 'source_table' => '-', 'sort_order' => 2],
            ['endpoint' => 'listgaji', 'field_key' => 'nip', 'field_label' => 'NIP', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 3],
            ['endpoint' => 'listgaji', 'field_key' => 'nama_skpd', 'field_label' => 'Nama SKPD', 'source_table' => 'satkers', 'sort_order' => 4],
            ['endpoint' => 'listgaji', 'field_key' => 'tipe_jabatan', 'field_label' => 'Tipe Jabatan', 'source_table' => 'Calculation', 'sort_order' => 5],
            ['endpoint' => 'listgaji', 'field_key' => 'nama_jabatan', 'field_label' => 'Nama Jabatan', 'source_table' => 'ref_jabatan_fungsional/ref_eselon', 'sort_order' => 6],
            ['endpoint' => 'listgaji', 'field_key' => 'eselon', 'field_label' => 'Eselon', 'source_table' => 'master_pegawai', 'sort_order' => 7],
            ['endpoint' => 'listgaji', 'field_key' => 'status_asn', 'field_label' => 'Status ASN (1=PNS, 2=PPPK)', 'source_table' => 'Fixed', 'sort_order' => 8],
            ['endpoint' => 'listgaji', 'field_key' => 'golongan', 'field_label' => 'Golongan', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 9],
            ['endpoint' => 'listgaji', 'field_key' => 'masa_kerja_golongan', 'field_label' => 'Masa Kerja Golongan', 'source_table' => 'master_pegawai', 'sort_order' => 10],
            ['endpoint' => 'listgaji', 'field_key' => 'alamat', 'field_label' => 'Alamat', 'source_table' => 'master_pegawai', 'sort_order' => 11],
            ['endpoint' => 'listgaji', 'field_key' => 'kode_bank', 'field_label' => 'Kode Bank', 'source_table' => 'master_pegawai', 'sort_order' => 12],
            ['endpoint' => 'listgaji', 'field_key' => 'nama_bank', 'field_label' => 'Nama Bank', 'source_table' => 'master_pegawai', 'sort_order' => 13],
            ['endpoint' => 'listgaji', 'field_key' => 'nomor_rekening', 'field_label' => 'Nomor Rekening', 'source_table' => 'master_pegawai', 'sort_order' => 14],
            ['endpoint' => 'listgaji', 'field_key' => 'jumlah_istri', 'field_label' => 'Jumlah Istri', 'source_table' => 'master_pegawai', 'sort_order' => 15],
            ['endpoint' => 'listgaji', 'field_key' => 'jumlah_anak', 'field_label' => 'Jumlah Anak', 'source_table' => 'master_pegawai', 'sort_order' => 16],
            ['endpoint' => 'listgaji', 'field_key' => 'gaji_pokok', 'field_label' => 'Gaji Pokok', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 17],
            ['endpoint' => 'listgaji', 'field_key' => 'tk_tunjangan_istri', 'field_label' => 'Tunjangan Istri', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 18],
            ['endpoint' => 'listgaji', 'field_key' => 'tk_tunjangan_anak', 'field_label' => 'Tunjangan Anak', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 19],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_tunjangan_eselon', 'field_label' => 'Tunjangan Eselon', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 20],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_tunjangan_fungsional', 'field_label' => 'Tunjangan Fungsional', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 21],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_tunjangan_struktural', 'field_label' => 'Tunjangan Struktural', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 22],
            ['endpoint' => 'listgaji', 'field_key' => 'tunjangan_umum', 'field_label' => 'Tunjangan Umum', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 23],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_beras', 'field_label' => 'Tunjangan Beras', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 24],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_pajak', 'field_label' => 'Tunjangan Pajak', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 25],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_khusus', 'field_label' => 'Tunjangan Khusus', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 26],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_terpencil', 'field_label' => 'Tunjangan Terpencil', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 27],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_guru', 'field_label' => 'Tunjangan Guru', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 28],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_tkd', 'field_label' => 'Tunjangan TKD', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 29],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_langka', 'field_label' => 'Tunjangan Langka', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 30],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_askes', 'field_label' => 'Tunjangan Askes', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 31],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_jkk', 'field_label' => 'Tunjangan JKK', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 32],
            ['endpoint' => 'listgaji', 'field_key' => 'tj_jkm', 'field_label' => 'Tunjangan JKM', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 33],
            ['endpoint' => 'listgaji', 'field_key' => 'pembulatan', 'field_label' => 'Pembulatan', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 34],
            ['endpoint' => 'listgaji', 'field_key' => 'jlh_kotor', 'field_label' => 'Jumlah Kotor', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 35],
            ['endpoint' => 'listgaji', 'field_key' => 'pot_iwp', 'field_label' => 'Potongan IWP', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 36],
            ['endpoint' => 'listgaji', 'field_key' => 'pot_pajak', 'field_label' => 'Potongan Pajak', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 37],
            ['endpoint' => 'listgaji', 'field_key' => 'pot_taperum', 'field_label' => 'Potongan Taperum', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 38],
            ['endpoint' => 'listgaji', 'field_key' => 'jlh_potongan', 'field_label' => 'Jumlah Potongan', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 39],
            ['endpoint' => 'listgaji', 'field_key' => 'jlh_bersih', 'field_label' => 'Jumlah Bersih', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 40],
            ['endpoint' => 'listgaji', 'field_key' => 'status_pajak', 'field_label' => 'Status Pajak', 'source_table' => 'tax_statuses/Calculation', 'sort_order' => 41],
            ['endpoint' => 'listgaji', 'field_key' => 'jenis_gaji', 'field_label' => 'Jenis Gaji', 'source_table' => 'gaji_pns/gaji_pppk', 'sort_order' => 42],
        ];

        foreach ($configs as $config) {
            ApiFieldConfig::updateOrCreate(
                ['endpoint' => $config['endpoint'], 'field_key' => $config['field_key']],
                [
                    'native_key' => $config['field_key'], // Default native_key is the original field_key
                    'field_label' => $config['field_label'],
                    'source_table' => $config['source_table'],
                    'sort_order' => $config['sort_order'],
                    'is_enabled' => true,
                ]
            );
        }
    }
}
