<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use App\Models\SpecialDeal;

echo "Current Server Time: " . Carbon::now() . "\n";
echo "Current Timezone: " . config('app.timezone') . "\n";

$deals = SpecialDeal::where('is_active', true)->get();
echo "Total Active Deals Found (ignoring time): " . $deals->count() . "\n";

foreach ($deals as $deal) {
    echo "Deal ID: " . $deal->id . "\n";
    echo "Start Time: " . $deal->start_time . "\n";
    echo "End Time: " . $deal->end_time . "\n";
    echo "Start <= Now? " . ($deal->start_time <= Carbon::now() ? 'YES' : 'NO') . "\n";
    echo "End >= Now? " . ($deal->end_time >= Carbon::now() ? 'YES' : 'NO') . "\n";
}
