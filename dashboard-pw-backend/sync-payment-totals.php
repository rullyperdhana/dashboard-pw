<?php
// Fix payment header totals based on details
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Syncing tb_payment totals with details ===\n";

    $payments = \App\Models\Payment::with('details')->get();
    $fixedCount = 0;

    foreach ($payments as $payment) {
        $actualTotal = $payment->details->sum('total_amoun');
        $actualCount = $payment->details->count();

        if (abs($payment->total_amoun - $actualTotal) > 0.01 || $payment->total_emplo != $actualCount) {
            echo "Fixing Payment ID #{$payment->id} ({$payment->month}/{$payment->year}):\n";
            echo "  Amount: {$payment->total_amoun} -> {$actualTotal}\n";
            echo "  People: {$payment->total_emplo} -> {$actualCount}\n";

            $payment->update([
                'total_amoun' => $actualTotal,
                'total_emplo' => $actualCount
            ]);
            $fixedCount++;
        }
    }

    echo "✅ Sync complete. Fixed $fixedCount payments.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
