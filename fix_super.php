<?php
$content = file_get_contents('/opt/autonomia/app/Filament/SuperAdmin/Pages/SuperAdminDashboard.php');
$startStr = "private function getDatabaseSizeMB(): ?float\n    {\n        try {\n";
$endStr = "        } catch (\Exception) {\n            return null;\n        }\n    }";

$startPos = strpos($content, $startStr);
$endPos = strpos($content, $endStr, $startPos) + strlen($endStr);

$newMethod = "private function getDatabaseSizeMB(): ?float
    {
        try {
            if (config('database.default') === 'pgsql') {
                \$result = DB::selectOne(\"SELECT pg_database_size(current_database()) AS size\");
                return round(\$result->size / 1024 / 1024, 2);
            } elseif (config('database.default') === 'mysql') {
                \$dbName = config('database.connections.mysql.database');
                \$result = DB::selectOne(\"SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = ?\", [\$dbName]);
                return round(\$result->size / 1024 / 1024, 2);
            }
            return null;
        } catch (\Exception) {
            return null;
        }
    }";

$newContent = substr($content, 0, $startPos) . $newMethod . substr($content, $endPos);
file_put_contents('/opt/autonomia/app/Filament/SuperAdmin/Pages/SuperAdminDashboard.php', $newContent);
