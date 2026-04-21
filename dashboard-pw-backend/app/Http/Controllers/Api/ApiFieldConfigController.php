<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiFieldConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiFieldConfigController extends Controller
{
    public function index()
    {
        $configs = ApiFieldConfig::orderBy('endpoint')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('endpoint');

        return response()->json([
            'status' => true,
            'data' => $configs,
        ]);
    }

    public function update(Request $request, $id)
    {
        $config = ApiFieldConfig::findOrFail($id);

        $request->validate([
            'is_enabled' => 'sometimes|boolean',
            'field_label' => 'sometimes|string|max:255',
            'field_key' => "sometimes|string|max:255|unique:api_field_configs,field_key,{$id},id,endpoint,{$config->endpoint}",
            'source_table' => 'sometimes|string|nullable|max:255',
        ]);

        $config->update($request->only(['is_enabled', 'field_label', 'field_key', 'source_table']));

        // Clear cache
        Cache::forget("api_field_config_{$config->endpoint}");

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi field berhasil diperbarui',
            'data' => $config,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'fields' => 'required|array',
            'fields.*.field_key' => 'required|string',
            'fields.*.is_enabled' => 'required|boolean',
        ]);

        $endpoint = $request->endpoint;

        foreach ($request->fields as $field) {
            ApiFieldConfig::where('endpoint', $endpoint)
                ->where('field_key', $field['field_key'])
                ->update(['is_enabled' => $field['is_enabled']]);
        }

        // Clear cache
        Cache::forget("api_field_config_{$endpoint}");

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi field berhasil diperbarui secara massal',
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $endpoint = $request->endpoint;

        ApiFieldConfig::where('endpoint', $endpoint)
            ->update(['is_enabled' => true]);

        // Clear cache
        Cache::forget("api_field_config_{$endpoint}");

        return response()->json([
            'status' => true,
            'message' => 'Konfigurasi field berhasil direset ke default',
        ]);
    }
}
