<?php
$content = file_get_contents('/var/www/config/session.php');
$content = str_replace("env('SESSION_CONNECTION', env('DB_CONNECTION', 'pgsql'))", "env('SESSION_CONNECTION', 'default')", $content);
file_put_contents('/var/www/config/session.php', $content);
