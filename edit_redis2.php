<?php
$f = '/var/www/vendor/laravel/framework/src/Illuminate/Cache/CacheManager.php';
$c = file_get_contents($f);
$c = preg_replace('/protected function createRedisDriver\(array \$config\)\s*\{/', 'protected function createRedisDriver(array $config) { file_put_contents("/tmp/cache_debug.txt", "STORE CONFIG IS: ".json_encode($config)."\n", FILE_APPEND);', $c);
file_put_contents($f, $c);
