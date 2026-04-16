<?php
$f = '/var/www/vendor/laravel/framework/src/Illuminate/Cache/RedisStore.php';
$c = file_get_contents($f);
$c = preg_replace('/public function __construct\(Redis \$redis, string \$prefix = \x27\x27, string \$connection = \x27default\x27\)\s*\{/', 'public function __construct(\$redis, \$prefix = "", \$connection = "default") { if (\$connection === "mysql") { file_put_contents("/tmp/redis_tracer.txt", (new \Exception)->getTraceAsString() . "\n\n", FILE_APPEND); }', $c);
file_put_contents($f, $c);
