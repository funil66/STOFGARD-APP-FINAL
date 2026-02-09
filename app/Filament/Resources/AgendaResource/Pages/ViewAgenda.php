<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAgenda extends ViewRecord
{
    protected static string $resource = AgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('concluir')
                ->label('Concluir Agendamento')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status !== 'concluido')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['status' => 'concluido']);
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Agendamento Concluído!')
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
