<?php
$f = '/var/www/vendor/laravel/framework/src/Illuminate/Redis/RedisManager.php';
$c = file_get_contents($f);
$c = preg_replace('/throw new InvalidArgumentException\(.*/', 'throw new InvalidArgumentException("Redis connection [{$name}] not configured. STORE CONF: " . json_encode(config("cache.stores.redis")) . " Default: " . config("cache.default") . " db: " . json_encode(config("database.redis")));', $c);
file_put_contents($f, $c);
