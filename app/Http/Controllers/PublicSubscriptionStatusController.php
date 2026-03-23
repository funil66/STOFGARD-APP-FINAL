<?php

namespace App\Http\Controllers;

use App\Models\Tenant;

class PublicSubscriptionStatusController extends Controller
{
    public function __invoke(Tenant $tenant)
    {
        return view('assinatura.status-publica', [
            'tenant' => $tenant,
        ]);
    }
}
