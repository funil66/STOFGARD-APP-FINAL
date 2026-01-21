<?php

namespace App\Http\Controllers;

use App\Models\GoogleToken;
use Filament\Notifications\Notification;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    /**
     * Redireciona para a página de autenticação do Google
     */
    public function redirectToGoogle()
    {
        $client = new GoogleClient;
        $client->setApplicationName('Stofgard App');
        $client->setScopes([Calendar::CALENDAR]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    /**
     * Recebe o callback do Google e salva o token
     */
    public function handleGoogleCallback(Request $request)
    {
        if (! $request->has('code')) {
            Notification::make()
                ->title('Erro na autenticação')
                ->body('Código de autorização não recebido.')
                ->danger()
                ->send();

            return redirect()->route('filament.admin.pages.google-calendar-settings');
        }

        try {
            $client = new GoogleClient;
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect'));

            // Troca o código pelo token de acesso
            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            if (isset($token['error'])) {
                throw new \Exception($token['error_description'] ?? 'Erro ao obter token');
            }

            // Configura o token no cliente
            $client->setAccessToken($token);

            // Obtém o ID do calendário principal
            $service = new Calendar($client);
            $calendar = $service->calendars->get('primary');

            // Salva ou atualiza o token no banco
            GoogleToken::updateOrCreate(
                ['user_id' => auth()->id()],
                [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires_at' => now()->addSeconds($token['expires_in']),
                    'google_calendar_id' => $calendar->getId(),
                ]
            );

            Notification::make()
                ->title('Google Calendar conectado!')
                ->body('Seus agendamentos serão sincronizados automaticamente.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao conectar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return redirect()->route('filament.admin.pages.google-calendar-settings');
    }
}
