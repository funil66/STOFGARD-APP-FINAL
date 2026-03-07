<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;

/**
 * Horizon Monitor — Link rápido para o dashboard de filas.
 *
 * Abre o Laravel Horizon (/horizon) em nova aba.
 * O acesso ao Horizon é controlado pelo HorizonServiceProvider gate (is_super_admin).
 */
class HorizonPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Monitor de Filas';

    protected static ?string $title = 'Monitor de Filas (Horizon)';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.super-admin.pages.horizon';

    public function mount(): void
    {
        // Redireciona direto para o Horizon ao acessar esta página
        $this->redirect('/horizon');
    }
}
