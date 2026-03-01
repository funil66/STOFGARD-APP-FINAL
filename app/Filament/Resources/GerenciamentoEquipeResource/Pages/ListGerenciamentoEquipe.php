<?php
namespace App\Filament\Resources\GerenciamentoEquipeResource\Pages;
use App\Filament\Resources\GerenciamentoEquipeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListGerenciamentoEquipe extends ListRecords {
    protected static string $resource = GerenciamentoEquipeResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Convidar Membro')]; }
}
