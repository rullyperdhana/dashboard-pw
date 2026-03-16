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
        Schema::table('skpd_mapping', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('skpd_mapping_source_name_type_unique');
            
            // Add new unique constraint including source_code
            $table->unique(['source_code', 'source_name', 'type'], 'skpd_mapping_source_code_source_name_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->dropUnique('skpd_mapping_source_code_source_name_type_unique');
            $table->unique(['source_name', 'type'], 'skpd_mapping_source_name_type_unique');
        });
    }
};
