<?php
$str = file_get_contents('app/Services/DigitalSealService.php');
$str = str_replace('\\$', '$', $str);
file_put_contents('app/Services/DigitalSealService.php', $str);
echo "Fixed backslashes\n";
