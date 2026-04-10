<?php

namespace App\Filament\Resources\OrcamentoResource\Pages;

use App\Filament\Resources\OrcamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrcamento extends EditRecord
{
    protected static string $resource = OrcamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('gerar_pdf_background')
                ->label('Gerar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Gerar PDF')
                ->modalDescription('O PDF será gerado em fila e ficará disponível em PDFs Gerados.')
                ->url(fn ($record) => route('orcamento.pdf', ['orcamento' => $record->id])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        app(\App\Actions\Financeiro\CalculateOrcamentoTotalsAction::class)->execute($this->record);
    }
}
