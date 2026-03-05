<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json([
                'status' => false,
                'message' => 'API Key diperlukan. Sertakan header X-API-Key pada request.',
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$key) {
            return response()->json([
                'status' => false,
                'message' => 'API Key tidak valid atau sudah dinonaktifkan.',
            ], 401);
        }

        // Update last used timestamp
        $key->update(['last_used_at' => now()]);

        return $next($request);
    }
}
