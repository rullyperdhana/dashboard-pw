<?php
// Fix idskpd in pegawai_pw based on skpd string matching
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Fixing idskpd in pegawai_pw ===\n";

    $invalids = DB::table('pegawai_pw')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))->from('skpd')->whereColumn('pegawai_pw.idskpd', 'skpd.id_skpd');
        })
        ->get();

    echo "Found " . $invalids->count() . " invalid idskpd entries.\n";

    $fixedCount = 0;
    foreach ($invalids as $i) {
        $searchName = trim($i->skpd);
        if (empty($searchName))
            continue;

        // Try direct match
        $skpd = DB::table('skpd')->where('nama_skpd', $searchName)->first();

        // Try fuzzy matches if not found
        if (!$skpd) {
            // Remove " DAERAH" suffix
            $name2 = str_replace(" DAERAH", "", $searchName);
            $skpd = DB::table('skpd')->where('nama_skpd', $name2)->first();
        }

        if (!$skpd) {
            // Remove commas
            $name3 = str_replace(",", "", $searchName);
            $skpd = DB::table('skpd')->where(DB::raw("REPLACE(nama_skpd, ',', '')"), $name3)->first();
        }

        if (!$skpd) {
            // Look for substring
            $skpd = DB::table('skpd')->where('nama_skpd', 'LIKE', '%' . substr($searchName, 0, 20) . '%')->first();
        }

        if ($skpd) {
            DB::table('pegawai_pw')->where('id', $i->id)->update(['idskpd' => $skpd->id_skpd]);
            $fixedCount++;
        } else {
            echo "⚠️ Could not match: $searchName\n";
        }
    }

    echo "✅ Fixed $fixedCount entries.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
