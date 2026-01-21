<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ConfiguracoesGerais extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.configuracoes-gerais';

    protected static ?string $navigationLabel = 'Configurações';

    protected static ?string $title = 'Configurações Gerais';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 99;
}
