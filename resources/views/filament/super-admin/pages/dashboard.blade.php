<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        @foreach([
            ['label' => 'Total de Usuários', 'value' => $stats['total_users'], 'icon' => '👥', 'color' => 'blue'],
            ['label' => 'Super Admins', 'value' => $stats['super_admins'], 'icon' => '🔑', 'color' => 'purple'],
            ['label' => 'Total de Clientes', 'value' => $stats['total_cadastros'], 'icon' => '🏢', 'color' => 'green'],
            ['label' => 'Orçamentos', 'value' => $stats['total_orcamentos'], 'icon' => '📋', 'color' => 'yellow'],
            ['label' => 'Ordens de Serviço', 'value' => $stats['total_os'], 'icon' => '🛠️', 'color' => 'orange'],
            ['label' => 'Banco de dados', 'value' => $stats['db_size_mb'] ? number_format($stats['db_size_mb'], 1) . ' MB' : 'N/D', 'icon' => '💾', 'color' => 'gray'],
        ] as $stat)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-3xl">{{ $stat['icon'] }}</span>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach

    </div>

    <div class="mt-8 rounded-xl border border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20 p-6">
        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">⚠️ Aviso de Segurança</h3>
        <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
            Você está no painel de Super Admin. Todas as ações aqui afetam diretamente os dados da plataforma.
            Impersonações são registradas em log com IP e timestamp.
            <strong>Nunca compartilhe acesso a este painel.</strong>
        </p>
    </div>

    <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
        <h3 class="text-lg font-semibold mb-4">🔗 Ações Rápidas</h3>
        <div class="flex flex-wrap gap-3">
            <a href="/horizon" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                📊 Horizon Dashboard
            </a>
            <a href="/super-admin/user-impersonations" 
               class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700">
                👤 Impersonar Usuário
            </a>
            <a href="/super-admin/tenants" 
               class="inline-flex items-center gap-2 rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                🏢 Gerenciar Tenants
            </a>
        </div>
    </div>
</x-filament-panels::page>
