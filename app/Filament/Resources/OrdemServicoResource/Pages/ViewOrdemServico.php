<?php

namespace App\Filament\Resources\OrdemServicoResource\Pages;

use App\Filament\Resources\OrdemServicoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrdemServico extends ViewRecord
{
    protected static string $resource = OrdemServicoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('iniciar_servico')
                ->label('📍 Iniciar Serviço')
                ->color('warning')
                ->visible(fn() => (filament()->getTenant()?->isElite() ?? false) && !$this->record->checkin_time && $this->record->status !== 'concluida')
                ->form([
                    \Filament\Forms\Components\Hidden::make('checkin_lat'),
                    \Filament\Forms\Components\Hidden::make('checkin_lng'),
                    \Filament\Forms\Components\ViewField::make('gps_tracker')
                        ->label('Localização GPS')
                        ->view('filament.forms.components.gps-tracker')
                ])
                ->modalHeading('📍 Iniciar Serviço')
                ->modalDescription('O sistema irá capturar sua localização atual para validar o início do serviço no local do cliente.')
                ->modalSubmitActionLabel('Confirmar Início')
                ->action(function (array $data) {
                    $this->record->update([
                        'checkin_lat' => $data['checkin_lat'] ?? null,
                        'checkin_lng' => $data['checkin_lng'] ?? null,
                        'checkin_ip' => request()->ip(),
                        'checkin_time' => now(),
                        'status' => 'em_andamento',
                    ]);
                    \Filament\Notifications\Notification::make()->title('Serviço Iniciado com Sucesso!')->success()->send();
                }),

            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
