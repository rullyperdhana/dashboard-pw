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
        // We use a raw query because index names might vary between environments
        $indexName = null;
        $indexes = DB::select("SHOW INDEX FROM skpd_mapping WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
        foreach ($indexes as $index) {
            // We are looking for the unique index on source_name/type
            if (str_contains($index->Key_name, 'source') || str_contains($index->Key_name, 'unique')) {
                $indexName = $index->Key_name;
                break;
            }
        }

        Schema::table('skpd_mapping', function (Blueprint $table) use ($indexName) {
            if ($indexName) {
                $table->dropUnique($indexName);
            }
            
            // Add new unique constraint including source_code
            $table->unique(['source_code', 'source_name', 'type'], 'skpd_mapping_unique_v2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->dropUnique('skpd_mapping_unique_v2');
            $table->unique(['source_name', 'type']);
        });
    }
};
