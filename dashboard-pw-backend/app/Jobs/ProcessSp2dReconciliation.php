<?php

namespace App\Jobs;

use App\Models\UploadJob;
use App\Services\Sp2dReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessSp2dReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bulan;
    protected $tahun;
    protected $tppReconMode;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct($bulan, $tahun, $tppReconMode, $jobId)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->tppReconMode = $tppReconMode;
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(Sp2dReconciliationService $service): void
    {
        $job = UploadJob::find($this->jobId);
        if (!$job) return;

        try {
            $job->markProcessing();
            $job->update(['progress' => 10]);

            // Calculate
            $results = $service->calculateReconciliation($this->bulan, $this->tahun, $this->tppReconMode);
            
            $job->update(['progress' => 80]);

            // Save to Cache (1 hour)
            $cacheKey = $service->getCacheKey($this->bulan, $this->tahun, $this->tppReconMode);
            Cache::put($cacheKey, $results, now()->addHours(1));

            // Mark job details
            $summary = [
                'total_groups' => count($results),
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
                'mode' => $this->tppReconMode
            ];

            $job->markCompleted($summary);
            
        } catch (\Throwable $e) {
            Log::error("Error processing SP2D Reconciliation job: " . $e->getMessage(), [
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
                'trace' => $e->getTraceAsString()
            ]);
            $job->markFailed($e->getMessage());
        }
    }
}
