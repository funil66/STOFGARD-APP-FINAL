<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\User;
use App\Models\Cadastro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SuperAdminDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Super Admin — Visão Geral';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.super-admin.pages.dashboard';

    /**
     * Estatísticas globais do SaaS.
     */
    public function getViewData(): array
    {
        return [
            'stats' => [
                'total_users' => User::count(),
                'super_admins' => User::where('is_super_admin', true)->count(),
                'total_cadastros' => Cadastro::count(),
                'total_orcamentos' => Orcamento::count(),
                'total_os' => OrdemServico::count(),
                'db_size_mb' => $this->getDatabaseSizeMB(),
            ],
        ];
    }

    private function getDatabaseSizeMB(): ?float
    {
        try {
            if (config('database.default') === 'pgsql') {
                $result = DB::selectOne("SELECT pg_database_size(current_database()) AS size");
                return round($result->size / 1024 / 1024, 2);
            }
            return null;
        } catch (\Exception) {
            return null;
        }
    }
}
