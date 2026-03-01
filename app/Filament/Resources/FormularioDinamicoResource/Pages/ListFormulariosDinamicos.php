<?php
namespace App\Filament\Resources\FormularioDinamicoResource\Pages;
use App\Filament\Resources\FormularioDinamicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFormulariosDinamicos extends ListRecords {
    protected static string $resource = FormularioDinamicoResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
