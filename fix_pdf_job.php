<?php
$file = 'app/Services/PdfQueueService.php';
$content = file_get_contents($file);
$old = "\$htmlContent = \App\Services\DigitalSealService::appendSeal(\$htmlContent, \$tipo, \$modeloId);\n        ProcessPdfJob::dispatch(\$modeloId, \$tipo, \$userId, \$htmlContent, \$recordId);";
$new = "\$htmlContent = \App\Services\DigitalSealService::appendSeal(\$htmlContent, \$tipo, \$modeloId);\n        ProcessPdfJob::dispatch(\$modeloId, \$tipo, \$userId, \$htmlContent, \$recordId);";
// Since the job uses \App\Models\PdfGeneration, make sure anything else is right
echo "Check";
