<?php
namespace App\Filament\Resources\GerenciamentoEquipeResource\Pages;
use App\Filament\Resources\GerenciamentoEquipeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditGerenciamentoEquipe extends EditRecord {
    protected static string $resource = GerenciamentoEquipeResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
