<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExcelValidationService
{
    /**
     * Validate Excel headers against expected headers.
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $expectedHeaders
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public static function validateHeaders($file, array $expectedHeaders)
    {
        try {
            // Read only the first row (headers)
            $rows = Excel::toArray(new \App\Imports\GenericImport, $file);
            
            if (empty($rows) || empty($rows[0])) {
                return [
                    'success' => false,
                    'message' => 'File Excel kosong atau tidak terbaca.'
                ];
            }

            $headers = array_map('trim', $rows[0][0]);
            
            // Check for missing required headers
            $missing = [];
            foreach ($expectedHeaders as $expected) {
                if (!in_array($expected, $headers)) {
                    $missing[] = $expected;
                }
            }

            if (!empty($missing)) {
                return [
                    'success' => false,
                    'message' => 'Kolom berikut tidak ditemukan: ' . implode(', ', $missing),
                    'found_headers' => $headers
                ];
            }

            return [
                'success' => true,
                'message' => 'Header Valid.',
                'preview' => array_slice($rows[0], 0, 6) // Return first 5 rows for preview
            ];

        } catch (\Exception $e) {
            Log::error('Excel Header Validation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat membaca file: ' . $e->getMessage()
            ];
        }
    }
}
