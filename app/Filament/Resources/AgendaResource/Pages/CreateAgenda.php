<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Services\GoogleCalendarService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAgenda extends CreateRecord
{
    protected static string $resource = AgendaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Adicionar informações de auditoria
        $user = Auth::user();
        $data['criado_por'] = strtoupper(substr($user->name, 0, 2));

        return $data;
    }

    protected function afterCreate(): void
    {
        // Tentar sincronizar com Google Calendar automaticamente
        try {
            $googleService = new GoogleCalendarService(Auth::id());

            if ($googleService->isConnected()) {
                $googleEventId = $googleService->createEvent($this->record);

                if ($googleEventId) {
                    $this->record->update(['google_event_id' => $googleEventId]);

                    \Filament\Notifications\Notification::make()
                        ->title('Evento criado e sincronizado')
                        ->body('Evento adicionado ao Google Calendar automaticamente')
                        ->success()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Erro ao sincronizar evento com Google Calendar', [
                'agenda_id' => $this->record->id,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
