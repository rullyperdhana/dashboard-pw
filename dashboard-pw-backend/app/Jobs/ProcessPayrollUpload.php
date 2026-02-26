<?php

namespace App\Jobs;

use App\Models\UploadJob;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Imports\PnsImport;
use App\Imports\PppkImport;
use App\Imports\TppImport;
use App\Imports\TpgImport;
use App\Models\TpgData;
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
        $type = $params['type']; // pns or pppk

        $job->updateProgress(0, 100);

        Excel::import(new TppImport($month, $year, $type), $filePath);
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
}
