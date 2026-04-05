<?php
$str = file_get_contents('app/Services/PdfService.php');
$str = str_replace('Log::info("SETTING NODE ENV");', '\Illuminate\Support\Facades\Log::info("SETTING NODE ENV");', $str);
file_put_contents('app/Services/PdfService.php', $str);
