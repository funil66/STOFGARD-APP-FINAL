<?php
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

$tenant = Tenant::find('d29e543c-6166-4214-909e-732169f47ea0');
tenancy()->initialize($tenant);
echo Schema::connection('tenant')->hasTable('perfis_garantia') ? "EXISTS\n" : "MISSING\n";
tenancy()->end();
