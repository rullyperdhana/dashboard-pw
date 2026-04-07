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
        // Use Laravel's database-agnostic Schema::getIndexes helper
        $indexes = Schema::getIndexes('skpd_mapping');
        
        foreach ($indexes as $index) {
            // Looking for a unique index (besides primary) that mentions 'source' or 'unique'
            if ($index['unique'] && $index['name'] !== 'primary') {
                if (str_contains($index['name'], 'source') || str_contains($index['name'], 'unique')) {
                    $indexName = $index['name'];
                    break;
                }
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
