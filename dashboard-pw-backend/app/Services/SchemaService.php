<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SchemaService
{
    /**
     * Perform all necessary database repairs
     */
    public function repairAll(): array
    {
        $results = [];

        try {
            $results['primary_keys'] = $this->repairPrimaryKeys();
            $results['primary_keys'] = $this->repairPrimaryKeys();
            $results['columns'] = $this->repairColumns();
            $results['mappings'] = $this->repairMappings();
            $results['sanctum'] = $this->repairSanctumTable();
            $results['sync'] = $this->syncTotals();

            Log::info('Automated database repair completed.', $results);
        } catch (\Exception $e) {
            Log::error('Automated database repair failed: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Ensure Primary Keys and Auto-Increment are set
     */
    protected function repairPrimaryKeys(): array
    {
        $log = [];

        // 1. users table
        if (!$this->hasPrimaryKey('users')) {
            DB::statement('ALTER TABLE users MODIFY id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)');
            $log[] = "Added PK/AI to users";
        }

        // 2. skpd table
        if (!$this->hasPrimaryKey('skpd')) {
            DB::statement('ALTER TABLE skpd MODIFY id_skpd INT NOT NULL, ADD PRIMARY KEY (id_skpd)');
            $log[] = "Added PK to skpd";
        }

        // 3. pegawai_pw table
        if (!$this->hasPrimaryKey('pegawai_pw')) {
            DB::statement('ALTER TABLE pegawai_pw MODIFY id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)');
            $log[] = "Added PK/AI to pegawai_pw";
        }

        // 4. tb_payment table
        if (!$this->hasPrimaryKey('tb_payment')) {
            DB::statement('ALTER TABLE tb_payment MODIFY id INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)');
            $log[] = "Added PK/AI to tb_payment";
        }

        return $log;
    }

    /**
     * Fix missing columns and naming mismatches
     */
    protected function repairColumns(): array
    {
        $log = [];

        // pegawai_pw columns
        if (!Schema::hasColumn('pegawai_pw', 'nik')) {
            DB::statement('ALTER TABLE pegawai_pw ADD COLUMN nik VARCHAR(255) NULL AFTER nip');
            $log[] = "Added nik to pegawai_pw";
        }
        if (!Schema::hasColumn('pegawai_pw', 'no_hp')) {
            DB::statement('ALTER TABLE pegawai_pw ADD COLUMN no_hp VARCHAR(50) NULL AFTER status');
            $log[] = "Added no_hp to pegawai_pw";
        }

        // tb_payment renames
        $this->renameIfMissing('tb_payment', 'total_amount', 'total_amoun', 'DECIMAL(15,2) NOT NULL DEFAULT 0', $log);
        $this->renameIfMissing('tb_payment', 'total_employees', 'total_emplo', 'INT NOT NULL DEFAULT 0', $log);
        $this->renameIfMissing('tb_payment', 'payment_date', 'payment_dat', 'DATE NULL', $log);

        // tb_payment_detail renames
        $this->renameIfMissing('tb_payment_detail', 'total_amount', 'total_amoun', 'DECIMAL(15,2) NOT NULL DEFAULT 0', $log);

        return $log;
    }

    /**
     * Helper to rename columns if naming mismatch exists
     */
    protected function renameIfMissing($table, $old, $new, $definition, &$log)
    {
        if (Schema::hasColumn($table, $old) && !Schema::hasColumn($table, $new)) {
            DB::statement("ALTER TABLE $table CHANGE $old $new $definition");
            $log[] = "Renamed $table.$old -> $new";
        }
    }

    /**
     * Fix SKPD Mappings (Fuzzy match names)
     */
    protected function repairMappings(): array
    {
        $log = [];
        $invalidEmployees = DB::table('pegawai_pw')
            ->where(function ($q) {
                $q->whereNull('idskpd')->orWhere('idskpd', 0);
            })
            ->whereNotNull('skpd')
            ->get();

        if ($invalidEmployees->count() > 0) {
            $fixed = 0;
            foreach ($invalidEmployees as $emp) {
                $skpd = DB::table('skpd')
                    ->where('nama_skpd', 'LIKE', '%' . $emp->skpd . '%')
                    ->first();
                if ($skpd) {
                    DB::table('pegawai_pw')->where('id', $emp->id)->update(['idskpd' => $skpd->id_skpd]);
                    $fixed++;
                }
            }
            if ($fixed > 0)
                $log[] = "Fixed $fixed SKPD mappings";
        }

        return $log;
    }

    /**
     * Re-sync header totals
     */
    protected function syncTotals(): array
    {
        $log = [];
        $payments = DB::table('tb_payment')->get();
        $fixed = 0;

        foreach ($payments as $p) {
            $actualSum = DB::table('tb_payment_detail')->where('payment_id', $p->id)->sum('total_amoun');
            $actualCount = DB::table('tb_payment_detail')->where('payment_id', $p->id)->count();

            if ($p->total_amoun != $actualSum || $p->total_emplo != $actualCount) {
                DB::table('tb_payment')->where('id', $p->id)->update([
                    'total_amoun' => $actualSum,
                    'total_emplo' => $actualCount
                ]);
                $fixed++;
            }
        }

        if ($fixed > 0)
            $log[] = "Synced totals for $fixed payments";
        return $log;
    }

    /**
     * Create Sanctum table if missing
     */
    protected function repairSanctumTable(): array
    {
        $log = [];
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
            $log[] = "Created personal_access_tokens table";
        }
        return $log;
    }

    /**
     * Check if a table has a primary key
     */
    protected function hasPrimaryKey($table): bool
    {
        $pks = DB::select("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
        return count($pks) > 0;
    }

    /**
     * Quick check if repair is needed
     */
    public static function isRepairNeeded(): bool
    {
        try {
            if (!Schema::hasTable('pegawai_pw'))
                return false;

            // 1. Missing columns
            if (!Schema::hasColumn('pegawai_pw', 'nik'))
                return true;
            if (!Schema::hasColumn('pegawai_pw', 'no_hp'))
                return true;

            // 2. Renamed columns (check if old names still exist)
            if (Schema::hasColumn('tb_payment', 'total_amount'))
                return true;
            if (Schema::hasColumn('tb_payment_detail', 'total_amount'))
                return true;

            // 3. Primary keys (check one)
            $pks = DB::select("SHOW KEYS FROM pegawai_pw WHERE Key_name = 'PRIMARY'");
            if (count($pks) === 0)
                return true;

            // 4. Sanctum Table
            if (!Schema::hasTable('personal_access_tokens'))
                return true;

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
