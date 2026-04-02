<?php
$str = file_get_contents('/var/www/app/Jobs/CreateTenantOwnerJob.php');
$str = str_replace(
    "\$data = \$this->tenant->data ?? [];\n        unset(\$data['pending_owner']);\n        \$this->tenant->update(['data' => \$data]);",
    "\$this->tenant->pending_owner = null;\n        \$this->tenant->save();",
    $str
);
file_put_contents('/var/www/app/Jobs/CreateTenantOwnerJob.php', $str);
