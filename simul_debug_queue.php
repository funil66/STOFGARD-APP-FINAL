<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\PdfGeneration::where('status', 'processing')->count();
echo "Processing: $count\n";

$failed = \App\Models\PdfGeneration::where('status', 'failed')->count();
echo "Failed: $failed\n";

$jobs = \App\Models\PdfGeneration::get()->toArray();
print_r($jobs);
