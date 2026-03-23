<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\User;
use App\Models\Cadastro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminDashboard extends Page
{
    protected static ?string $slug = '';

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
                'total_users' => $this->safeCount(User::class, 'users'),
                'super_admins' => $this->safeCount(User::class, 'users', ['is_super_admin' => true]),
                'total_cadastros' => $this->safeCount(Cadastro::class, 'cadastros'),
                'total_orcamentos' => $this->safeCount(Orcamento::class, 'orcamentos'),
                'total_os' => $this->safeCount(OrdemServico::class, 'ordens_servico'),
                'db_size_mb' => $this->getDatabaseSizeMB(),
            ],
        ];
    }

    private function safeCount(string $modelClass, string $table, array $where = []): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }

            $query = $modelClass::query();

            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }

            return (int) $query->count();
        } catch (\Throwable) {
            return 0;
        }
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
