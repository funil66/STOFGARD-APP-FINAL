<?php
namespace App\Filament\Resources\GerenciamentoEquipeResource\Pages;
use App\Filament\Resources\GerenciamentoEquipeResource;
use Filament\Resources\Pages\CreateRecord;
class CreateGerenciamentoEquipe extends CreateRecord {
    protected static string $resource = GerenciamentoEquipeResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
