<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\GoogleToken;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected GoogleClient $client;

    protected GoogleToken $token;

    public function __construct(GoogleToken $token)
    {
        $this->token = $token;
        $this->client = $this->getClient();
    }

    /**
     * Configura o cliente Google
     */
    protected function getClient(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName('Autonomia Ilimitada App');
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        // Define o token de acesso
        $client->setAccessToken([
            'access_token' => $this->token->access_token,
            'refresh_token' => $this->token->refresh_token,
            'expires_in' => $this->token->expires_at ? $this->token->expires_at->timestamp - now()->timestamp : 3600,
        ]);

        // Renova o token se necessário
        if ($this->token->needsRefresh() && $this->token->refresh_token) {
            try {
                $newToken = $client->fetchAccessTokenWithRefreshToken($this->token->refresh_token);

                if (isset($newToken['access_token'])) {
                    $this->token->update([
                        'access_token' => $newToken['access_token'],
                        'expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Erro ao renovar token do Google: '.$e->getMessage());
            }
        }

        return $client;
    }

    /**
     * Sincroniza uma agenda com o Google Calendar
     */
    public function syncAgenda(Agenda $agenda): ?string
    {
        try {
            $service = new Calendar($this->client);

            // Se já tem um evento vinculado, atualiza
            if ($agenda->google_event_id) {
                return $this->updateEvent($service, $agenda);
            }

            // Caso contrário, cria um novo evento
            return $this->createEvent($service, $agenda);

        } catch (Exception $e) {
            Log::error('Erro ao sincronizar agenda com Google Calendar: '.$e->getMessage(), [
                'agenda_id' => $agenda->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cria um novo evento no Google Calendar
     */
    protected function createEvent(Calendar $service, Agenda $agenda): ?string
    {
        $event = new Event([
            'summary' => $agenda->titulo,
            'description' => $this->getEventDescription($agenda),
            'location' => $agenda->endereco_completo ?? $agenda->local,
            'colorId' => $this->mapColorToGoogleColorId($agenda->cor),
            'start' => new EventDateTime([
                'dateTime' => $agenda->data_hora_inicio->toRfc3339String(),
                'timeZone' => 'America/Sao_Paulo',
            ]),
            'end' => new EventDateTime([
                'dateTime' => $agenda->data_hora_fim->toRfc3339String(),
                'timeZone' => 'America/Sao_Paulo',
            ]),
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => $agenda->minutos_antes_lembrete ?? 60],
                ],
            ],
        ]);

        $calendarId = $this->token->google_calendar_id ?? 'primary';
        $createdEvent = $service->events->insert($calendarId, $event);

        return $createdEvent->getId();
    }

    /**
     * Atualiza um evento existente no Google Calendar
     */
    protected function updateEvent(Calendar $service, Agenda $agenda): ?string
    {
        $calendarId = $this->token->google_calendar_id ?? 'primary';

        try {
            $event = $service->events->get($calendarId, $agenda->google_event_id);

            $event->setSummary($agenda->titulo);
            $event->setDescription($this->getEventDescription($agenda));
            $event->setLocation($agenda->endereco_completo ?? $agenda->local);
            $event->setColorId($this->mapColorToGoogleColorId($agenda->cor));

            $event->setStart(new EventDateTime([
                'dateTime' => $agenda->data_hora_inicio->toRfc3339String(),
                'timeZone' => 'America/Sao_Paulo',
            ]));

            $event->setEnd(new EventDateTime([
                'dateTime' => $agenda->data_hora_fim->toRfc3339String(),
                'timeZone' => 'America/Sao_Paulo',
            ]));

            $updatedEvent = $service->events->update($calendarId, $event->getId(), $event);

            return $updatedEvent->getId();

        } catch (Exception $e) {
            Log::error('Erro ao atualizar evento no Google Calendar: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Remove um evento do Google Calendar
     */
    public function deleteEvent(string $eventId): bool
    {
        try {
            $service = new Calendar($this->client);
            $calendarId = $this->token->google_calendar_id ?? 'primary';

            $service->events->delete($calendarId, $eventId);

            return true;

        } catch (Exception $e) {
            Log::error('Erro ao deletar evento do Google Calendar: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Monta a descrição do evento com informações relevantes
     */
    protected function getEventDescription(Agenda $agenda): string
    {
        $description = $agenda->descricao ?? '';

        if ($agenda->cliente) {
            $description .= "\n\n👤 Cliente: ".$agenda->cliente->nome;
            if ($agenda->cliente->telefone) {
                $description .= "\n📱 Telefone: ".$agenda->cliente->telefone;
            }
        }

        if ($agenda->ordemServico) {
            $description .= "\n\n🔧 OS #".$agenda->ordemServico->numero_os;
        }

        if ($agenda->tipo) {
            $tipos = [
                'visita' => '🚗 Visita',
                'servico' => '🧼 Serviço',
                'follow_up' => '📞 Follow-up',
                'reuniao' => '👥 Reunião',
                'outro' => '📌 Outro',
            ];
            $description .= "\n\n".($tipos[$agenda->tipo] ?? 'Tipo: '.$agenda->tipo);
        }

        if ($agenda->status) {
            $status = [
                'agendado' => '📅 Agendado',
                'confirmado' => '✔️ Confirmado',
                'em_andamento' => '🔄 Em andamento',
                'concluido' => '✅ Concluído',
                'cancelado' => '❌ Cancelado',
            ];
            $description .= "\nStatus: ".($status[$agenda->status] ?? $agenda->status);
        }

        return $description;
    }

    /**
     * Mapeia cores hex para IDs de cores do Google Calendar
     */
    protected function mapColorToGoogleColorId(?string $hexColor): string
    {
        $colorMap = [
            '#3b82f6' => '1', // Azul
            '#22c55e' => '10', // Verde
            '#f97316' => '6', // Laranja
            '#8b5cf6' => '3', // Roxo
            '#6b7280' => '8', // Cinza
            '#ef4444' => '11', // Vermelho
            '#eab308' => '5', // Amarelo
        ];

        return $colorMap[$hexColor] ?? '1'; // Padrão: azul
    }

    /**
     * Obtém o ID do calendário principal do usuário
     */
    public function getPrimaryCalendarId(): ?string
    {
        try {
            $service = new Calendar($this->client);
            $calendar = $service->calendars->get('primary');

            return $calendar->getId();

        } catch (Exception $e) {
            Log::error('Erro ao obter ID do calendário: '.$e->getMessage());

            return null;
        }
    }
}
