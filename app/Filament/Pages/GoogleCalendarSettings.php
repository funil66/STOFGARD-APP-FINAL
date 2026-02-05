<?php

namespace App\Filament\Pages;

use App\Models\GoogleToken;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GoogleCalendarSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.google-calendar-settings';

    protected static ?string $navigationLabel = 'Google Calendar';

    protected static ?string $title = 'Integração Google Calendar';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 99;

    public ?GoogleToken $token = null;

    public function mount(): void
    {
        $this->token = GoogleToken::where('user_id', auth()->id())->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Conectar Google Calendar')
                ->icon('heroicon-o-link')
                ->color('success')
                ->url(route('google.auth'))
                ->visible(fn () => ! $this->token),

            Action::make('disconnect')
                ->label('Desconectar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Desconectar Google Calendar')
                ->modalDescription('Tem certeza? Os agendamentos não serão mais sincronizados automaticamente.')
                ->action(function () {
                    if ($this->token) {
                        $this->token->delete();

                        Notification::make()
                            ->title('Google Calendar desconectado')
                            ->success()
                            ->send();

                        $this->token = null;
                    }
                })
                ->visible(fn () => $this->token),
        ];
    }
}
