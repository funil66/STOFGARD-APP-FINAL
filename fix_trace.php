<?php
$f = '/var/www/vendor/laravel/framework/src/Illuminate/Cache/CacheManager.php';
$c = file_get_contents($f);
$c = preg_replace(
    '/protected function createRedisDriver\(array \$config\)\s*\{/',
    'protected function createRedisDriver(array $config) { if (($config["connection"] ?? null) === "mysql") { file_put_contents("/tmp/cm_trace.txt", (new \Exception)->getTraceAsString() . "\nCONFIG=" . json_encode($config), FILE_APPEND); }',
    $c
);
file_put_contents($f, $c);
