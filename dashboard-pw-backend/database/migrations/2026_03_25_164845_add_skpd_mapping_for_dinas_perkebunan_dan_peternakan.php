<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('skpd_mapping')->updateOrInsert(
            [
                'skpd_id' => 59,
                'source_code' => '013',
                'type' => 'all'
            ],
            [
                'source_name' => 'DINAS PERKEBUNAN DAN PETERNAKAN',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('skpd_mapping')
            ->where('skpd_id', 59)
            ->where('source_code', '013')
            ->delete();
    }
};
