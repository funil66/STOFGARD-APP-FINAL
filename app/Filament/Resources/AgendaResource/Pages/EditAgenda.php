<?php

namespace App\Filament\Resources\AgendaResource\Pages;

use App\Filament\Resources\AgendaResource;
use App\Services\GoogleCalendarService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditAgenda extends EditRecord
{
    protected static string $resource = AgendaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->after(function () {
                    // Deletar do Google Calendar se existir
                    if ($this->record->google_event_id) {
                        try {
                            $googleService = new GoogleCalendarService(Auth::id());
                            $googleService->deleteEvent($this->record->google_event_id);
                        } catch (\Exception $e) {
                            \Log::error('Erro ao deletar evento do Google Calendar', [
                                'google_event_id' => $this->record->google_event_id,
                                'erro' => $e->getMessage(),
                            ]);
                        }
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Atualizar informações de auditoria
        $user = Auth::user();
        $data['atualizado_por'] = strtoupper(substr($user->name, 0, 2));

        return $data;
    }

    protected function afterSave(): void
    {
        // Atualizar no Google Calendar se estiver sincronizado
        if ($this->record->google_event_id) {
            try {
                $googleService = new GoogleCalendarService(Auth::id());
                $updated = $googleService->updateEvent($this->record);

                if ($updated) {
                    \Filament\Notifications\Notification::make()
                        ->title('Evento atualizado')
                        ->body('Sincronizado com Google Calendar')
                        ->success()
                        ->send();
                }
            } catch (\Exception $e) {
                \Log::error('Erro ao atualizar evento no Google Calendar', [
                    'agenda_id' => $this->record->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }
    }
}
