<?php
use Illuminate\Support\Facades\DB;

$db = 'tenantd29e543c-6166-4214-909e-732169f47ea0';
DB::statement('USE `' . $db . '`');
DB::statement('ALTER TABLE orcamentos DROP COLUMN gateway_cobranca_id');

