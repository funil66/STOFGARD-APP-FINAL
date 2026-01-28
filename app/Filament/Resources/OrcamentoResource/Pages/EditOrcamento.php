<?php
namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource; use Filament\Actions; use Filament\Resources\Pages\EditRecord;

class EditOrcamento extends EditRecord { protected static string $resource = OrcamentoResource::class;

protected function getHeaderActions(): array
{
    return [
        Actions\DeleteAction::make(),
        Actions\Action::make('pdf')
            ->label('Baixar PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->url(fn ($record) => route('orcamento.pdf', $record))
            ->openUrlInNewTab(),
    ];
}
protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}
} 
