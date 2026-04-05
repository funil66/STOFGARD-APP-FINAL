<?php
$path = 'app/Services/PdfQueueService.php';
$str = file_get_contents($path);
$str = str_replace('use Illuminate\Support\Facades\Log;', "use Illuminate\Support\Facades\Log;\nuse App\Jobs\ProcessPdfJob;", $str);
file_put_contents($path, $str);
