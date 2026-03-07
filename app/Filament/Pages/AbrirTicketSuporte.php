<?php

namespace App\Filament\Pages;

use App\Models\TicketSuporte;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Página "Precisa de Ajuda?" — permite que o inquilino
 * abra um ticket de suporte direto para o Super Admin.
 */
class AbrirTicketSuporte extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Precisa de Ajuda?';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 99;
    protected static ?string $title = 'Suporte do Sistema';
    protected static string $view = 'filament.pages.abrir-ticket-suporte';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📨 Enviar Solicitação de Suporte')
                    ->description('Descreva o problema ou dúvida. Nossa equipe responderá o mais breve possível.')
                    ->schema([
                        Forms\Components\TextInput::make('assunto')
                            ->label('Assunto')
                            ->placeholder('Ex: Erro ao gerar PDF, Dúvida sobre pagamento...')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('prioridade')
                            ->label('Prioridade')
                            ->options([
                                'baixa' => '🟢 Baixa — Dúvida geral',
                                'media' => '🟡 Média — Problema que atrapalha',
                                'alta' => '🔴 Alta — Sistema travado / urgente',
                            ])
                            ->default('media')
                            ->required(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição Detalhada')
                            ->placeholder('Explique o que aconteceu, qual tela, o que você fez...')
                            ->required()
                            ->rows(6)
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        TicketSuporte::create([
            'tenant_id' => tenant('id'),
            'user_id' => auth()->id(),
            'assunto' => $data['assunto'],
            'descricao' => $data['descricao'],
            'prioridade' => $data['prioridade'],
            'status' => 'aberto',
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Ticket enviado com sucesso! 🎫')
            ->body('Nossa equipe foi notificada e responderá em breve.')
            ->success()
            ->send();
    }

    /**
     * Lista os tickets anteriores deste tenant.
     */
    public function getTicketsProperty()
    {
        return TicketSuporte::where('tenant_id', tenant('id'))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }
}
