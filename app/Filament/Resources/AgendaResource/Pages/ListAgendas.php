<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Services\GoogleCalendarService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAgendas extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Ação: Importar do Google Calendar
            Actions\Action::make('importar_google')
                ->label('Importar do Google')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('data_inicio')
                        ->label('Data Inicial')
                        ->default(now()->startOfMonth())
                        ->required()
                        ->native(false),

                    \Filament\Forms\Components\DatePicker::make('data_fim')
                        ->label('Data Final')
                        ->default(now()->endOfMonth())
                        ->required()
                        ->native(false)
                        ->after('data_inicio'),
                ])
                ->action(function (array $data) {
                    try {
                        $googleService = new GoogleCalendarService(Auth::id());

                        if (! $googleService->isConnected()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Google Calendar não conectado')
                                ->body('Configure sua conta do Google em Configurações')
                                ->warning()
                                ->send();

                            return;
                        }

                        $startDate = new \DateTime($data['data_inicio']);
                        $endDate = new \DateTime($data['data_fim']);

                        $imported = $googleService->importEvents($startDate, $endDate);

                        \Filament\Notifications\Notification::make()
                            ->title("$imported eventos importados")
                            ->body('Eventos do Google Calendar adicionados à agenda')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro ao importar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Ação: Visualizar Calendário
            Actions\Action::make('visualizar_calendario')
                ->label('Visualizar Calendário')
                ->icon('heroicon-o-calendar')
                ->color('success')
                ->url(fn (): string => route('filament.admin.pages.calendario')),

            Actions\CreateAction::make()
                ->label('Novo Evento'),
        ];
    }
}
