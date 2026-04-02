<?php
$str = file_get_contents('/var/www/app/Jobs/CreateTenantOwnerJob.php');
$str = str_replace(
    "'role'               => 'dono',\n                'acesso_financeiro'  => true,",
    "",
    $str
);
$str = str_replace(
    "'role'               => 'dono',\n                    'acesso_financeiro'  => true,",
    "",
    $str
);
file_put_contents('/var/www/app/Jobs/CreateTenantOwnerJob.php', $str);
