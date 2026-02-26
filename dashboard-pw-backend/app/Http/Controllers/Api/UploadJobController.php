<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UploadJob;
use App\Jobs\ProcessPayrollUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadJobController extends Controller
{
    /**
     * Submit a new upload job.
     * Accepts file + params, stores file, creates job record, dispatches to queue.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv',
            'type' => 'required|in:pns,pppk,tpp,tpg',
        ]);

        $type = $request->input('type');

        // Type-specific validation
        $extraRules = $this->getExtraValidationRules($type);
        if (!empty($extraRules)) {
            $request->validate($extraRules);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();

            // Ensure upload directory exists
            $uploadDir = storage_path('app/uploads/pending');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            // Generate unique filename and move file directly
            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $storedPath = 'uploads/pending/' . $fileName;
            $fullPath = $uploadDir . '/' . $fileName;

            // Verify file was actually stored
            if (!file_exists($fullPath)) {
                Log::error("Upload failed - file not found after move", [
                    'target_path' => $fullPath,
                    'original_name' => $originalName,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan file ke server. Periksa permission folder storage.',
                ], 500);
            }

            Log::info("File stored successfully", [
                'path' => $fullPath,
                'size' => filesize($fullPath),
            ]);

            // Build params based on type
            $params = $this->buildParams($request, $type);

            // Create upload job record
            $uploadJob = UploadJob::create([
                'type' => $type,
                'file_name' => $originalName,
                'file_path' => $storedPath,
                'status' => 'pending',
                'params' => $params,
                'user_id' => auth()->id(),
            ]);

            // Dispatch to queue
            ProcessPayrollUpload::dispatch($uploadJob->id);

            Log::info("Upload Job #{$uploadJob->id} created and dispatched", [
                'type' => $type,
                'file' => $originalName,
                'stored_path' => $storedPath,
                'file_exists' => file_exists($fullPath),
                'params' => $params,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File diterima dan sedang diproses di background.',
                'data' => [
                    'job_id' => $uploadJob->id,
                    'status' => $uploadJob->status,
                    'file_name' => $originalName,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create upload job', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check status of a specific upload job (for polling).
     */
    public function show($id)
    {
        $uploadJob = UploadJob::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $uploadJob->id,
                'type' => $uploadJob->type,
                'file_name' => $uploadJob->file_name,
                'status' => $uploadJob->status,
                'progress' => $uploadJob->progress,
                'total_rows' => $uploadJob->total_rows,
                'processed_rows' => $uploadJob->processed_rows,
                'error_message' => $uploadJob->error_message,
                'error_detail' => $uploadJob->error_detail,
                'result_summary' => $uploadJob->result_summary,
                'started_at' => $uploadJob->started_at,
                'completed_at' => $uploadJob->completed_at,
                'created_at' => $uploadJob->created_at,
            ],
        ]);
    }

    /**
     * List recent upload jobs for the current user.
     */
    public function index(Request $request)
    {
        $query = UploadJob::orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $jobs = $query->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Get validation rules based on upload type.
     */
    private function getExtraValidationRules(string $type): array
    {
        return match ($type) {
            'pns', 'pppk' => [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000',
                'jenis_gaji' => 'required|in:Induk,Susulan,Kekurangan,Terusan',
            ],
            'tpp' => [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2020|max:2030',
                'tpp_type' => 'required|in:pns,pppk',
            ],
            'tpg' => [
                'triwulan' => 'required|integer|min:1|max:4',
                'tahun' => 'required|integer|min:2020|max:2030',
                'jenis' => 'required|in:INDUK,SUSULAN',
            ],
            default => [],
        };
    }

    /**
     * Build params array based on upload type.
     */
    private function buildParams(Request $request, string $type): array
    {
        return match ($type) {
            'pns', 'pppk' => [
                'month' => (int) $request->input('month'),
                'year' => (int) $request->input('year'),
                'jenis_gaji' => $request->input('jenis_gaji'),
            ],
            'tpp' => [
                'month' => (int) $request->input('month'),
                'year' => (int) $request->input('year'),
                'type' => $request->input('tpp_type'),
            ],
            'tpg' => [
                'triwulan' => (int) $request->input('triwulan'),
                'tahun' => (int) $request->input('tahun'),
                'jenis' => $request->input('jenis'),
            ],
            default => [],
        };
    }
}
