<?php

namespace App\Http\Controllers;

use App\Models\Configuracao;
use App\Models\Tenant;
use App\Services\ServiceTypeManager;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    /**
     * Exibe a "Vitrine Pública" (Link na Bio) do tenant.
     * URL: /v/{slug}
     */
    public function show(string $slug)
    {
        // 1. Busca o tenant ativo pelo slug
        $tenant = Tenant::findBySlug($slug);

        if (!$tenant) {
            abort(404, 'Página não encontrada ou inativa.');
        }

        // 2. Inicializa o contexto do banco de dados do tenant
        tenancy()->initialize($tenant);

        // 3. Busca configurações (nome, logo, cores, contatos, etc.)
        $config = Configuracao::first();

        // 4. Busca os serviços ativos oferecidos pelo tenant
        $servicos = ServiceTypeManager::getAll();

        // 5. Finaliza o escopo para não vazar a tenancy
        tenancy()->end();

        return view('tenant.profile', [
            'tenant' => $tenant,
            'config' => $config,
            'servicos' => $servicos,
            'slug' => $slug,
        ]);
    }
}
