<?php

namespace App\Jobs;

use App\Models\UploadJob;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Imports\PnsImport;
use App\Imports\PppkImport;
use App\Imports\TppImport;
use App\Imports\TpgImport;
use App\Imports\SatkerImport;
use App\Helpers\DbfReader;
use App\Models\MasterPegawai;
use App\Models\MasterKeluarga;
use App\Models\Satker;
use App\Models\TpgData;
use App\Models\HistoryGajiPokok;
use App\Models\RefJabatanFungsional;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPayrollUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes max
    public $tries = 1;     // Don't retry — just report error

    protected $uploadJobId;

    public function __construct(int $uploadJobId)
    {
        $this->uploadJobId = $uploadJobId;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $uploadJob = UploadJob::findOrFail($this->uploadJobId);
        $uploadJob->markProcessing();

        $params = $uploadJob->params;
        $filePath = storage_path('app/' . $uploadJob->file_path);

        if (!file_exists($filePath)) {
            $uploadJob->markFailed(
                'File tidak ditemukan di server.',
                "Path: {$filePath}"
            );
            return;
        }

        try {
            switch ($uploadJob->type) {
                case 'pns':
                    $this->processPns($uploadJob, $filePath, $params);
                    break;
                case 'pppk':
                    $this->processPppk($uploadJob, $filePath, $params);
                    break;
                case 'tpp':
                    $this->processTpp($uploadJob, $filePath, $params);
                    break;
                case 'tpg':
                    $this->processTpg($uploadJob, $filePath, $params);
                    break;
                case 'master_pegawai':
                    $this->processMasterPegawai($uploadJob, $filePath, $params);
                    break;
                case 'master_keluarga':
                    $this->processMasterKeluarga($uploadJob, $filePath, $params);
                    break;
                case 'satker_ref':
                    $this->processSatkerRef($uploadJob, $filePath, $params);
                    break;
                case 'payroll_dbf':
                    $this->processPayrollDbf($uploadJob, $filePath, $params);
                    break;
                case 'history_gpok':
                    $this->processHistoryGPok($uploadJob, $filePath, $params);
                    break;
                case 'jabfung_ref':
                    $this->processJabfungRef($uploadJob, $filePath, $params);
                    break;
                case 'nik_update':
                    $this->processNikUpdate($uploadJob, $filePath, $params);
                    break;
                default:
                    $uploadJob->markFailed("Tipe upload tidak dikenal: {$uploadJob->type}");
                    return;
            }
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorLines = [];
            foreach (array_slice($failures, 0, 10) as $failure) {
                $errorLines[] = "Baris {$failure->row()}: kolom '{$failure->attribute()}' - " . implode(', ', $failure->errors());
            }
            $totalErrors = count($failures);
            $detail = implode("\n", $errorLines);
            if ($totalErrors > 10) {
                $detail .= "\n... dan " . ($totalErrors - 10) . " error lainnya.";
            }

            $uploadJob->markFailed(
                "Validasi gagal: {$totalErrors} baris bermasalah.",
                $detail
            );

            Log::error("Upload Job #{$this->uploadJobId} validation failed", [
                'errors' => count($failures),
            ]);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Make common errors more readable
            if (str_contains($message, 'memory')) {
                $message = 'Server kehabisan memori. File terlalu besar untuk diproses. Coba pecah file menjadi bagian yang lebih kecil.';
            } elseif (str_contains($message, 'Allowed memory size')) {
                $message = 'Memori server tidak cukup untuk memproses file ini.';
            } elseif (str_contains($message, 'SQLSTATE')) {
                $message = 'Kesalahan database: format data tidak sesuai dengan kolom yang diharapkan.';
            }

            $uploadJob->markFailed(
                $message,
                "File: {$uploadJob->file_name}\n" .
                "Error class: " . get_class($e) . "\n" .
                "Line: " . $e->getLine() . "\n" .
                "Trace (ringkas): " . substr($e->getTraceAsString(), 0, 1000)
            );

            Log::error("Upload Job #{$this->uploadJobId} failed", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        } finally {
            // Clean up the uploaded file
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    private function processPns(UploadJob $job, string $filePath, array $params): void
    {
        $month = $params['month'];
        $year = $params['year'];
        $jenisGaji = $params['jenis_gaji'];

        $job->updateProgress(0, 100);

        // Delete existing data
        $deletedCount = GajiPns::where('bulan', $month)
            ->where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji)
            ->delete();

        Log::info("Upload Job #{$this->uploadJobId}: Deleted {$deletedCount} existing PNS records");
        $job->updateProgress(10, 100);

        // Import
        Excel::import(new PnsImport($month, $year, $jenisGaji), $filePath);
        $job->updateProgress(90, 100);

        // Count results
        $totalRecords = GajiPns::where('bulan', $month)
            ->where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji)
            ->count();

        $job->markCompleted([
            'total_records' => $totalRecords,
            'deleted_records' => $deletedCount,
            'message' => "Berhasil import {$totalRecords} data PNS ({$jenisGaji}) untuk periode {$month}/{$year}.",
        ]);
    }

    private function processPppk(UploadJob $job, string $filePath, array $params): void
    {
        $month = $params['month'];
        $year = $params['year'];
        $jenisGaji = $params['jenis_gaji'];

        $job->updateProgress(0, 100);

        $deletedCount = GajiPppk::where('bulan', $month)
            ->where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji)
            ->delete();

        $job->updateProgress(10, 100);

        Excel::import(new PppkImport($month, $year, $jenisGaji), $filePath);
        $job->updateProgress(90, 100);

        $totalRecords = GajiPppk::where('bulan', $month)
            ->where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji)
            ->count();

        $job->markCompleted([
            'total_records' => $totalRecords,
            'deleted_records' => $deletedCount,
            'message' => "Berhasil import {$totalRecords} data PPPK ({$jenisGaji}) untuk periode {$month}/{$year}.",
        ]);
    }

    private function processTpp(UploadJob $job, string $filePath, array $params): void
    {
        $month = $params['month'];
        $year = $params['year'];
        $type = $params['type'] ?? 'pns'; // pns or pppk
        if (isset($params['tpp_type'])) $type = $params['tpp_type'];
        $jenisGaji = $params['jenis_gaji'] ?? 'Induk';

        $job->updateProgress(0, 100);

        Excel::import(new TppImport($month, $year, $type, $jenisGaji), $filePath);
        $job->updateProgress(90, 100);

        $job->markCompleted([
            'message' => "Berhasil import data TPP ({$type}) untuk periode {$month}/{$year}.",
        ]);
    }

    private function processTpg(UploadJob $job, string $filePath, array $params): void
    {
        $triwulan = $params['triwulan'];
        $tahun = $params['tahun'];
        $jenis = $params['jenis'];

        $job->updateProgress(0, 100);

        if ($jenis === 'INDUK') {
            TpgData::where('triwulan', $triwulan)
                ->where('tahun', $tahun)
                ->where('jenis', 'INDUK')
                ->delete();
        }

        $job->updateProgress(10, 100);

        Excel::import(new TpgImport($triwulan, $tahun, $jenis), $filePath);
        $job->updateProgress(90, 100);

        $count = TpgData::where('triwulan', $triwulan)
            ->where('tahun', $tahun)
            ->where('jenis', $jenis)
            ->count();

        $jenisLabel = $jenis === 'INDUK' ? 'Induk' : 'Susulan';

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil import {$count} data TPG {$jenisLabel} Triwulan {$triwulan} Tahun {$tahun}.",
        ]);
    }

    private function processMasterPegawai(UploadJob $job, string $filePath, array $params): void
    {
        $batch = $params['batch'];
        $reader = new DbfReader($filePath);
        $totalRecords = $reader->getRecordCount();
        $job->updateProgress(0, $totalRecords);

        $dataBuffer = [];
        $count = 0;
        $batchSize = 500;

        DB::transaction(function () use ($reader, $job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
            $reader->each(function ($record, $index) use ($job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
                $data = $this->mapPegawaiFields($record);
                $data['upload_batch'] = $batch;
                $data['created_at'] = now();
                $data['updated_at'] = now();

                $dataBuffer[] = $data;
                $count++;

                if (count($dataBuffer) >= $batchSize) {
                    // Use upsert to handle updates for existing NIPs
                    MasterPegawai::upsert($dataBuffer, ['nip'], array_keys($data));
                    $dataBuffer = [];
                    $job->updateProgress($count, $totalRecords);
                }
            });

            if (!empty($dataBuffer)) {
                MasterPegawai::upsert($dataBuffer, ['nip'], array_keys($dataBuffer[0]));
            }
        });

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil mengimport {$count} data Master Pegawai (Batch: {$batch}).",
        ]);
    }

    private function processMasterKeluarga(UploadJob $job, string $filePath, array $params): void
    {
        $batch = $params['batch'];
        $reader = new DbfReader($filePath);
        $totalRecords = $reader->getRecordCount();
        $job->updateProgress(0, $totalRecords);

        $dataBuffer = [];
        $count = 0;
        $batchSize = 1000;

        DB::transaction(function () use ($reader, $job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
            $reader->each(function ($record, $index) use ($job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
                $data = $this->mapKeluargaFields($record);
                $data['upload_batch'] = $batch;
                $data['created_at'] = now();
                $data['updated_at'] = now();

                $dataBuffer[] = $data;
                $count++;

                if (count($dataBuffer) >= $batchSize) {
                    MasterKeluarga::insert($dataBuffer);
                    $dataBuffer = [];
                    $job->updateProgress($count, $totalRecords);
                }
            });

            if (!empty($dataBuffer)) {
                MasterKeluarga::insert($dataBuffer);
            }
        });

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil mengimport {$count} data Master Keluarga (Batch: {$batch}).",
        ]);
    }

    protected function mapPegawaiFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'niplama' => $record['niplama'] ?? null,
            'nipawal' => $record['nipawal'] ?? null,
            'nokarpeg' => $record['nokarpeg'] ?? null,
            'nama' => $record['nama'] ?? 'Tanpa Nama',
            'glrdepan' => $record['glrdepan'] ?? null,
            'glrbelakan' => $record['glrbelakan'] ?? null,
            'kdjenkel' => $record['kdjenkel'] ?? null,
            'tempatlhr' => $record['tempatlhr'] ?? null,
            'tgllhr' => $record['tgllhr'] ?? null,
            'agama' => $record['agama'] ?? null,
            'pendidikan' => $record['pendidikan'] ?? null,
            'kdstawin' => $record['kdstawin'] ?? null,
            'jistri' => $record['jistri'] ?? null,
            'janak' => $record['janak'] ?? null,
            'kdpangkat' => $record['kdpangkat'] ?? null,
            'blgolt' => $record['blgolt'] ?? null,
            'mkgolt' => $record['mkgolt'] ?? null,
            'masker' => $record['masker'] ?? null,
            'kdskpd' => $record['kdskpd'] ?? null,
            'kdsatker' => $record['kdsatker'] ?? null,
            'kdfungsi' => $record['kdfungsi'] ?? null,
            'kdeselon' => $record['kdeselon'] ?? null,
            'kdstruk' => $record['kdstruk'] ?? null,
            'gapok' => $record['gapok'] ?? 0,
            'prsngapok' => $record['prsngapok'] ?? null,
            'tjfungsi' => $record['tjfungsi'] ?? 0,
            'tjeselon' => $record['tjeselon'] ?? 0,
            'tjkhusus' => $record['tjkhusus'] ?? 0,
            'tjlangka' => $record['tjlangka'] ?? 0,
            'tjterpenci' => $record['tjterpenci'] ?? 0,
            'tjtkd' => $record['tjtkd'] ?? 0,
            'tjguru' => $record['tjguru'] ?? 0,
            'tjstruk' => $record['tjstruk'] ?? 0,
            'taperum' => $record['taperum'] ?? 0,
            'kdberas' => $record['kdberas'] ?? null,
            'kdlangka' => $record['kdlangka'] ?? null,
            'kdterpenci' => $record['kdterpenci'] ?? null,
            'kdtjkhusus' => $record['kdtjkhusus'] ?? null,
            'kdtkd' => $record['kdtkd'] ?? null,
            'kdguru' => $record['kdguru'] ?? null,
            'kdhitung' => $record['kdhitung'] ?? null,
            'kd_jns_peg' => $record['kd_jns_peg'] ?? null,
            'tmtcapeg' => $record['tmtcapeg'] ?? null,
            'tmtkgb' => $record['tmtkgb'] ?? null,
            'tmtkgbyad' => $record['tmtkgbyad'] ?? null,
            'tmtberlaku' => $record['tmtcycle'] ?? null, // Fixed: record might have updated names
            'tmtskmt' => $record['tmtskmt'] ?? null,
            'tmtstop' => $record['tmtstop'] ?? null,
            'tmttabel' => $record['tmttabel'] ?? null,
            'bup' => $record['bup'] ?? null,
            'kdstapeg' => $record['kdstapeg'] ?? null,
            'kdirdhata' => $record['kdirdhata'] ?? null,
            'pirdhata' => $record['pirdhata'] ?? 0,
            'kdkorpri' => $record['kdkorpri'] ?? null,
            'pkorpri' => $record['pkorpri'] ?? 0,
            'kdkoperasi' => $record['kdkoperasi'] ?? null,
            'pkoperasi' => $record['pkoperasi'] ?? 0,
            'psewa' => $record['psewa'] ?? 0,
            'kdcabtaspe' => $record['kdcabtaspe'] ?? null,
            'kodebyr' => $record['kodebyr'] ?? null,
            'kdssbp' => $record['kdssbp'] ?? null,
            'noktp' => $record['noktp'] ?? null,
            'npwp' => $record['npwp'] ?? null,
            'npwpz' => $record['npwpz'] ?? null,
            'norek' => $record['norek'] ?? null,
            'nohandphon' => $record['nohandphon'] ?? null,
            'notelp' => $record['notelp'] ?? null,
            'nodosir' => $record['nodosir'] ?? null,
            'nosks' => $record['nosks'] ?? null,
            'alamat' => $record['alamat'] ?? null,
            'kddati1' => $record['kddati1'] ?? null,
            'kddati2' => $record['kddati2'] ?? null,
            'kddati3' => $record['kddati3'] ?? null,
            'kddati4' => $record['kddati4'] ?? null,
            'kddati1_al' => $record['kddati1_al'] ?? null,
            'kddati2_al' => $record['kddati2_al'] ?? null,
            'kdjnstrans' => $record['kdjnstrans'] ?? null,
            'induk_bank' => $record['induk_bank'] ?? null,
            'jnsguru' => $record['jnsguru'] ?? 0,
            'zakat_dg' => $record['zakat_dg'] ?? 0,
            'kd_infaq' => $record['kd_infaq'] ?? 0,
            'catatan' => $record['catatan'] ?? null,
            'inputer' => $record['inputer'] ?? null,
            'updstamp' => $record['updstamp'] ?? null,
        ];
    }

    protected function mapKeluargaFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'nmkel' => $record['nmkel'] ?? 'Tanpa Nama',
            'kdhubkel' => $record['kdhubkel'] ?? null,
            'kdjenkel' => $record['kdjenkel'] ?? null,
            'tgllhr' => $record['tgllhr'] ?? null,
            'tglnikah' => $record['tglnikah'] ?? null,
            'tglcerai' => $record['tglcerai'] ?? null,
            'tglwafat' => $record['tglwafat'] ?? null,
            'tglsks' => $record['tglsks'] ?? null,
            'tatsks' => $record['tatsks'] ?? null,
            'glrdepan' => $record['glrdepan'] ?? null,
            'glrbelakan' => $record['glrbelakan'] ?? null,
            'kdtunjang' => $record['kdtunjang'] ?? null,
            'kdstawin' => $record['kdstawin'] ?? 0,
            'nipsuamiis' => $record['nipsuamiis'] ?? null,
            'pekerjaan' => $record['pekerjaan'] ?? null,
            'nosrtnikah' => $record['nosrtnikah'] ?? null,
            'nosrtcerai' => $record['nosrtcerai'] ?? null,
            'nosrtwafat' => $record['nosrtwafat'] ?? null,
            'noaktalahi' => $record['noaktalahi'] ?? null,
            'nosks' => $record['nosks'] ?? null,
            'kddati1' => $record['kddati1'] ?? null,
            'kddati2' => $record['kddati2'] ?? null,
            'inputer' => $record['inputer'] ?? null,
            'updstamp' => $record['updstamp'] ?? null,
        ];
    }

    private function processPayrollDbf(UploadJob $job, string $filePath, array $params): void
    {
        $month = $params['month'];
        $year = $params['year'];
        $jenisGaji = $params['jenis_gaji'];

        $job->updateProgress(0, 100);

        DB::transaction(function () use ($job, $filePath, $month, $year, $jenisGaji, $params) {
            // Delete existing data for both
            GajiPns::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->delete();
            GajiPppk::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->delete();

            $reader = new DbfReader($filePath);
            $totalRecords = $reader->getRecordCount();
            $job->updateProgress(0, $totalRecords);

            $pnsData = [];
            $pppkData = [];
            $count = 0;
            $batchSize = 200;

            $reader->each(function ($record, $index) use (&$pnsData, &$pppkData, &$count, $batchSize, $totalRecords, $job, $month, $year, $jenisGaji) {
                $mapped = $this->mapPayrollFields($record);
                $mapped['bulan'] = $month;
                $mapped['tahun'] = $year;
                $mapped['jenis_gaji'] = $jenisGaji;
                $mapped['created_at'] = now();
                $mapped['updated_at'] = now();

                $kdJnsPeg = (int) ($record['kd_jns_peg'] ?? 0);
                if ($kdJnsPeg === 4) {
                    $pppkData[] = $mapped;
                } else {
                    // Default to PNS if not specifically PPPK (2 or 1)
                    $pnsData[] = $mapped;
                }

                $count++;

                // Batch insert for performance
                if (count($pnsData) >= $batchSize) {
                    GajiPns::insert($pnsData);
                    $pnsData = [];
                }
                if (count($pppkData) >= $batchSize) {
                    GajiPppk::insert($pppkData);
                    $pppkData = [];
                }

                if ($count % 500 === 0) {
                    $job->updateProgress($count, $totalRecords);
                }
            });

            // Insert remaining
            if (!empty($pnsData)) {
                GajiPns::insert($pnsData);
            }
            if (!empty($pppkData)) {
                GajiPppk::insert($pppkData);
            }

            $job->updateProgress($count, $totalRecords);
        });

        $pnsCount = GajiPns::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->count();
        $pppkCount = GajiPppk::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->count();

        $job->markCompleted([
            'total_records' => GajiPns::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->count() + GajiPppk::where('bulan', $month)->where('tahun', $year)->where('jenis_gaji', $jenisGaji)->count(),
            'pns_count' => $pnsCount,
            'pppk_count' => $pppkCount,
            'message' => "Berhasil mengimport data Gaji (PNS: {$pnsCount}, PPPK: {$pppkCount}) untuk periode {$month}/{$year}.",
        ]);
    }

    protected function mapPayrollFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'nama' => $record['nama'] ?? 'Unknown',
            'golongan' => $record['mkgolt'] ?? null,
            'kdpangkat' => $record['kdpangkat'] ?? null,
            'jabatan' => null, // Not typically in this DBF format
            'skpd' => $record['nmskpd'] ?? 'Unknown',
            'satker' => $record['nmsatker'] ?? null,
            'kdskpd' => $record['kdskpd'] ?? null,
            'kdsatker' => $record['kdsatker'] ?? null,
            'kdjenkel' => $record['kdjenkel'] ?? null,
            'pendidikan' => $record['pendidikan'] ?? null,
            'norek' => $record['norek'] ?? null,
            'npwp' => $record['npwp'] ?? null,
            'noktp' => $record['noktp'] ?? null,
            // Tunjangan
            'gaji_pokok' => $gaji_pokok = (float) ($record['gapok'] ?? 0),
            'tunj_istri' => $tunj_istri = (float) ($record['tjistri'] ?? 0),
            'tunj_anak' => $tunj_anak = (float) ($record['tjanak'] ?? 0),
            'tunj_fungsional' => $tunj_fungsional = (float) ($record['tjfungsi'] ?? 0),
            'tunj_struktural' => $tunj_struktural = (float) ($record['tjstruk'] ?? 0),
            'tunj_umum' => $tunj_umum = (float) ($record['tjumum'] ?? 0),
            'tunj_beras' => $tunj_beras = (float) ($record['tjberas'] ?? 0),
            'tunj_pph' => $tunj_pph = (float) ($record['tjpajak'] ?? 0),
            'tunj_tpp' => $tunj_tpp = (float) ($record['tjtpp'] ?? 0),
            'tunj_eselon' => $tunj_eselon = (float) ($record['tjeselon'] ?? 0),
            'tunj_guru' => $tunj_guru = (float) ($record['tjguru'] ?? 0),
            'tunj_langka' => $tunj_langka = (float) ($record['tjlangka'] ?? 0),
            'tunj_tkd' => $tunj_tkd = (float) ($record['tjtkd'] ?? 0),
            'tunj_terpencil' => $tunj_terpencil = (float) ($record['tjterpencil'] ?? 0),
            'tunj_khusus' => $tunj_khusus = (float) ($record['tjkhusus'] ?? 0),
            'tunj_askes' => $tunj_askes = (float) ($record['tjaskes'] ?? 0),
            'tunj_kk' => $tunj_kk = (float) ($record['tjkk'] ?? 0),
            'tunj_km' => $tunj_km = (float) ($record['tjkm'] ?? 0),
            'pembulatan' => $pembulatan = (float) ($record['tbulat'] ?? 0),
            
            // Re-calculate Kotor (Ensuring all allowances are included)
            'kotor' => $kotor = ($gaji_pokok + $tunj_istri + $tunj_anak + $tunj_fungsional + $tunj_struktural + 
                        $tunj_umum + $tunj_beras + $tunj_pph + $tunj_tpp + $tunj_eselon + $tunj_guru + 
                        $tunj_langka + $tunj_tkd + $tunj_terpencil + $tunj_khusus + $tunj_askes + $tunj_kk + $tunj_km + $pembulatan),

            // Potongan
            'pot_iwp' => $pot_iwp = (float) ($record['piwp'] ?? 0),
            'pot_iwp1' => $pot_iwp1 = (float) ($record['piwp1'] ?? 0),
            'pot_iwp8' => $pot_iwp8 = (float) ($record['piwp8'] ?? 0),
            'pot_askes' => $pot_askes = (float) ($record['paskes'] ?? 0),
            'pot_pph' => $pot_pph = (float) ($record['ppajak'] ?? 0),
            'pot_bulog' => $pot_bulog = (float) ($record['pbulog'] ?? 0),
            'pot_taperum' => $pot_taperum = (float) ($record['ptaperum'] ?? 0),
            'pot_sewa' => $pot_sewa = (float) ($record['psewa'] ?? 0),
            'pot_hutang' => $pot_hutang = (float) ($record['phutang'] ?? 0),
            'pot_korpri' => $pot_korpri = (float) ($record['pkorpri'] ?? 0),
            'pot_irdhata' => $pot_irdhata = (float) ($record['pirdhata'] ?? 0),
            'pot_koperasi' => $pot_koperasi = (float) ($record['pkoperasi'] ?? 0),
            'pot_jkk' => $pot_jkk = (float) ($record['pjkk'] ?? 0),
            'pot_jkm' => $pot_jkm = (float) ($record['pjkm'] ?? 0),
            
            // Re-calculate Total Potongan
            'total_potongan' => $total_potongan = ($pot_iwp + $pot_iwp1 + $pot_iwp8 + $pot_askes + $pot_pph + 
                                $pot_bulog + $pot_taperum + $pot_sewa + $pot_hutang + $pot_korpri + 
                                $pot_irdhata + $pot_koperasi + $pot_jkk + $pot_jkm),
            
            // Re-calculate Bersih
            'bersih' => ($kotor - $total_potongan),
        ];
    }


    private function processSatkerRef(UploadJob $job, string $filePath, array $params): void
    {
        $job->updateProgress(0, 100);
        $start = microtime(true);

        Excel::import(new SatkerImport(), $filePath);

        $end = microtime(true);
        $duration = round($end - $start, 2);
        $count = Satker::count();

        $job->updateProgress(100, 100);

        Log::info("Upload Job #{$this->uploadJobId}: Satker reference update took {$duration} seconds. Total records: {$count}");

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil mengupdate {$count} data referensi Satker/SKPD dalam {$duration} detik.",
        ]);
    }

    private function processHistoryGPok(UploadJob $job, string $filePath, array $params): void
    {
        $batch = $params['batch'] ?? null;
        $reader = new DbfReader($filePath);
        $totalRecords = $reader->getRecordCount();
        $job->updateProgress(0, $totalRecords);

        $dataBuffer = [];
        $count = 0;
        $batchSize = 1000;

        DB::transaction(function () use ($reader, $job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
            $reader->each(function ($record, $index) use ($job, $batch, &$dataBuffer, &$count, $batchSize, $totalRecords) {
                $data = $this->mapHistoryGPokFields($record);
                $data['upload_batch'] = $batch;
                $data['created_at'] = now();
                $data['updated_at'] = now();

                if ($batch && str_contains($batch, '-')) {
                    $parts = explode('-', $batch);
                    if (count($parts) >= 2) {
                        $data['tahun'] = (int) $parts[0];
                        $data['bulan'] = (int) $parts[1];
                    }
                }

                $dataBuffer[] = $data;
                $count++;

                if (count($dataBuffer) >= $batchSize) {
                    HistoryGajiPokok::insert($dataBuffer);
                    $dataBuffer = [];
                    $job->updateProgress($count, $totalRecords);
                }
            });

            if (!empty($dataBuffer)) {
                HistoryGajiPokok::insert($dataBuffer);
            }

            $job->updateProgress($count, $totalRecords);
        });

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil mengimport {$count} data Riwayat Gaji Pokok (Batch: {$batch}).",
        ]);
    }

    protected function mapHistoryGPokFields(array $record): array
    {
        return [
            'nip' => $record['nip'] ?? null,
            'nama' => $record['nama'] ?? null,
            'gapok' => (int) ($record['gapok'] ?? 0),
            'tmt_berlaku' => $record['tmtberlak'] ?? null,
            'no_sk' => $record['nosk'] ?? null,
            'tmt_sk' => $record['tmtsk'] ?? null,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        $uploadJob = UploadJob::find($this->uploadJobId);
        if ($uploadJob && $uploadJob->status !== 'failed') {
            $uploadJob->markFailed(
                'Proses terhenti secara tidak terduga: ' . $exception->getMessage(),
                $exception->getTraceAsString()
            );
        }
    }

    private function processJabfungRef(UploadJob $job, string $filePath, array $params): void
    {
        $job->updateProgress(0, 100);
        $start = microtime(true);

        // Truncate existing data and re-import
        RefJabatanFungsional::truncate();
        $job->updateProgress(10, 100);

        Excel::import(new \App\Imports\JabatanFungsionalImport(), $filePath);

        $end = microtime(true);
        $duration = round($end - $start, 2);
        $count = RefJabatanFungsional::count();

        $job->updateProgress(100, 100);

        Log::info("Upload Job #{$this->uploadJobId}: Jabatan Fungsional reference update took {$duration} seconds. Total records: {$count}");

        $job->markCompleted([
            'total_records' => $count,
            'message' => "Berhasil mengupdate {$count} data referensi Jabatan Fungsional dalam {$duration} detik.",
        ]);
    }

    protected function processNikUpdate(UploadJob $uploadJob, string $filePath, array $params): void
    {
        $import = new \App\Imports\NikUpdateImport();
        
        \Maatwebsite\Excel\Facades\Excel::import($import, $filePath);
        
        $uploadJob->markCompleted([
            'message' => "NIK berhasil diperbarui untuk {$import->updatedCount} pegawai.",
            'updated_count' => $import->updatedCount,
            'not_found_count' => $import->notFoundCount,
            'errors' => $import->errors,
        ]);
    }
}
