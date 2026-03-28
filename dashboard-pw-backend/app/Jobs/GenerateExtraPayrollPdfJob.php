<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UploadJob;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\ExtraPayrollPppkPw;

class GenerateExtraPayrollPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;

    protected $jobId;
    protected $type;
    protected $year;
    protected $month;
    protected $filters;
    protected $userId;
    protected $userInstitution;
    protected $userRole;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $type, $year, $month, $filters, $userId, $userInstitution, $userRole)
    {
        $this->jobId = $jobId;
        $this->type = $type;
        $this->year = $year;
        $this->month = $month;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->userInstitution = $userInstitution;
        $this->userRole = $userRole;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        ini_set('memory_limit', '4096M');
        set_time_limit(1200);
        
        $uploadJob = UploadJob::find($this->jobId);
        if (!$uploadJob) return;

        $uploadJob->markProcessing();

        try {
            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $method = Setting::where('key', $this->type . '_pppk_pw_method')->value('value') ?? 'proporsional';

            $uploadJob->updateProgress(10, 100);

            // Fetch Data
            $query = ExtraPayrollPppkPw::where('type', $this->type)
                ->where('year', $this->year)
                ->where('month', $this->month);

            if ($this->userRole === 'operator' && !empty($this->userInstitution)) {
                $skpdName = DB::table('skpd')->where('id_skpd', $this->userInstitution)->value('nama_skpd');
                $query->where('skpd_name', 'like', $skpdName . '%');
            }

            if (!empty($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('skpd_name', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('skpd_name')->orderBy('nama')->get();
            $recordCount = $records->count();
            \Illuminate\Support\Facades\Log::info("PDF Job [{$this->jobId}]: Menemukan {$recordCount} data untuk dikirim ke PDF.");
            
            if ($records->isEmpty()) {
                \Illuminate\Support\Facades\Log::warning("PDF Job [{$this->jobId}]: Kueri menghasilkan 0 data. Cek Filter: Type={$this->type}, Year={$this->year}, Month={$this->month}");
            }

            $uploadJob->updateProgress(30, 100);

            // Process Grouped Data
            $groupedData = $records->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) {
                $skpdId = DB::table('skpd')->where('nama_skpd', $skpdName)->value('id_skpd');
                $signatory = $skpdId ? DB::table('report_settings')->where('skpdid', $skpdId)->first() : null;

                return [
                    'skpd_name' => $skpdName,
                    'signatory' => $signatory,
                    'pptk_groups' => $skpdItems->groupBy(function ($item) {
                        return $item->pptk_nama ?: 'Tanpa PPTK';
                    })->map(function ($pptkItems, $pptkName) {
                        $firstItem = $pptkItems->first();
                        return [
                            'pptk_nama' => $pptkName,
                            'pptk_nip' => $firstItem->pptk_nip,
                            'pptk_jabatan' => $firstItem->pptk_jabatan,
                            'sub_giat_groups' => $pptkItems->groupBy('nama_sub_giat')->map(function ($subGiatItems, $subGiatName) {
                                return [
                                    'sub_giat_name' => $subGiatName,
                                    'employees' => $subGiatItems->toArray(),
                                    'subtotal_thr' => $subGiatItems->sum('payroll_amount'),
                                    'employee_count' => $subGiatItems->count(),
                                    'qr_code' => null
                                ];
                            })->values(),
                            'total_pptk_thr' => $pptkItems->sum('payroll_amount'),
                            'total_pptk_employees' => $pptkItems->count()
                        ];
                    })->values(),
                    'total_employees_skpd' => $skpdItems->count(),
                    'total_thr_skpd' => $skpdItems->sum('payroll_amount')
                ];
            })->values();

            $dataArray = json_decode(json_encode($groupedData), true);
            $uploadJob->updateProgress(60, 100);

            $viewName = $this->type === 'thr' ? 'reports.thr_pppk_pw' : 'reports.thr_pppk_pw'; 
            $title = $this->type === 'thr' ? 'Tunjangan Hari Raya (THR)' : 'Gaji Ke-13';

            \Illuminate\Support\Facades\Log::info("PDF Job [{$this->jobId}]: Memulai Rendering DomPDF...");
            
            $pdf = Pdf::loadView($viewName, [
                'data'             => $groupedData, 
                'recordCount'      => $recordCount,
                'year'             => $this->year,
                'month'            => $this->month,
                'thrMonthName'     => $monthNames[(int)$this->month] ?? '',
                'calculationBasis' => 'Data Tersimpan (Database) - Metode: ' . ($method === 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12'),
                'printDate'        => now()->locale('id')->isoFormat('D MMMM YYYY'),
                'thrMethod'        => $method,
                'title'            => $title
            ])->setPaper('a4', 'landscape');

            $uploadJob->updateProgress(90, 100);

            $filename = strtoupper($this->type) . "_PPPK_PW_{$this->year}_{$this->month}_" . time() . ".pdf";
            
            $pdfOutput = $pdf->output();
            \Illuminate\Support\Facades\Log::info("PDF Job [{$this->jobId}]: Rendering Selesai. Ukuran file: " . strlen($pdfOutput) . " bytes");
            
            Storage::disk('public')->put("exports/{$filename}", $pdfOutput);
            
            $uploadJob->markCompleted([
                'download_url' => Storage::disk('public')->url("exports/{$filename}")
            ]);

        } catch (\Exception $e) {
            $uploadJob->markFailed($e->getMessage(), $e->getTraceAsString());
        }
    }
}
