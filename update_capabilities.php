<?php
$file = 'tests/DuskTestCase.php';
$content = file_get_contents($file);

$search = "ChromeOptions::CAPABILITY, \$options";
$replace = "ChromeOptions::CAPABILITY, \$options\n            )->setCapability('browserless:token', 'localtoken')";

$newContent = str_replace($search, $replace, $content);
file_put_contents($file, $newContent);
