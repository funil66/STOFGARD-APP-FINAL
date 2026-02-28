<?php

namespace App\Filament\Pages;

use App\Models\Cadastro;
use App\Models\Orcamento;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FunilVendas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static string $view = 'filament.pages.funil-vendas';

    protected static ?string $title = '🎯 Funil de Vendas CRM';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    // Filtros públicos
    public ?string $busca = '';

    public ?string $filtroVendedor = null;

    public ?string $filtroPeriodo = 'todos';

    // Definição das colunas do Kanban
    public array $statuses = [
        'novo' => [
            'title' => '🌟 Novo Lead',
            'color' => 'bg-gradient-to-br from-gray-50 to-gray-100 border-gray-300',
            'icon' => 'heroicon-o-star',
            'badge_color' => 'bg-gray-500',
        ],
        'contato_realizado' => [
            'title' => '💬 Contato Feito',
            'color' => 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-300',
            'icon' => 'heroicon-o-chat-bubble-left-right',
            'badge_color' => 'bg-blue-500',
        ],
        'agendado' => [
            'title' => '📅 Visita Agendada',
            'color' => 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-300',
            'icon' => 'heroicon-o-calendar',
            'badge_color' => 'bg-yellow-500',
        ],
        'proposta_enviada' => [
            'title' => '✈️ Proposta Enviada',
            'color' => 'bg-gradient-to-br from-purple-50 to-purple-100 border-purple-300',
            'icon' => 'heroicon-o-paper-airplane',
            'badge_color' => 'bg-purple-500',
        ],
        'em_negociacao' => [
            'title' => '💰 Em Negociação',
            'color' => 'bg-gradient-to-br from-orange-50 to-orange-100 border-orange-300',
            'icon' => 'heroicon-o-currency-dollar',
            'badge_color' => 'bg-orange-500',
        ],
        'aprovado' => [
            'title' => '✅ Fechado / Ganho',
            'color' => 'bg-gradient-to-br from-green-50 to-green-100 border-green-300',
            'icon' => 'heroicon-o-check-badge',
            'badge_color' => 'bg-green-500',
        ],
        'perdido' => [
            'title' => '❌ Perdido',
            'color' => 'bg-gradient-to-br from-red-50 to-red-100 border-red-300',
            'icon' => 'heroicon-o-x-circle',
            'badge_color' => 'bg-red-500',
        ],
    ];

    public function getViewData(): array
    {
        $query = Orcamento::query()
            ->with(['cliente'])
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderBy('updated_at', 'desc');

        // Aplicar filtros
        if ($this->busca) {
            $query->where(function ($q) {
                $termo = "%{$this->busca}%";
                $q->where('numero', 'like', $termo)
                    ->orWhereHas('cliente', fn($q) => $q->where('nome', 'like', $termo));
            });
        }

        if ($this->filtroVendedor) {
            $query->where('vendedor_id', $this->filtroVendedor);
        }

        if ($this->filtroPeriodo !== 'todos') {
            $periodo = match ($this->filtroPeriodo) {
                'hoje' => now()->startOfDay(),
                'semana' => now()->startOfWeek(),
                'mes' => now()->startOfMonth(),
                default => null,
            };
            if ($periodo) {
                $query->where('created_at', '>=', $periodo);
            }
        }

        $orcamentos = $query->get();

        // Calcular estatísticas
        $estatisticas = [];
        foreach ($this->statuses as $key => $data) {
            $items = $orcamentos->where('etapa_funil', $key);
            $estatisticas[$key] = [
                'count' => $items->count(),
                'total' => $items->sum(fn($item) => $item->valor_efetivo),
            ];
        }

        return [
            'orcamentos' => $orcamentos,
            'statuses' => $this->statuses,
            'estatisticas' => $estatisticas,
            'vendedores' => Cadastro::where('tipo', 'vendedor')->pluck('nome', 'id'),
        ];
    }

    public function updateStatus($recordId, $status)
    {
        $orcamento = Orcamento::find($recordId);

        if ($orcamento) {
            $orcamento->update(['etapa_funil' => $status]);

            // Sincronizar status principal quando mover para aprovado ou perdido
            if ($status === 'aprovado') {
                $orcamento->update(['status' => 'aprovado']);
            } elseif ($status === 'perdido') {
                $orcamento->update(['status' => 'rejeitado']);
            }

            Notification::make()
                ->title('✅ Etapa Atualizada')
                ->body("Lead movido para: {$this->statuses[$status]['title']}")
                ->success()
                ->send();

            // Recarrega a página
            $this->redirect(static::getUrl());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // BOTÃO: NOVO LEAD
            Action::make('novoLead')
                ->label('+ Novo Lead')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->modalHeading('🌟 Criar Novo Lead')
                ->modalDescription('Capture rapidamente um novo lead no funil de vendas')
                ->modalWidth('2xl')
                ->form([
                    Grid::make(['default' => 1, 'sm' => 2])->schema([
                        // DADOS DO CLIENTE
                        TextInput::make('nome_cliente')
                            ->label('Nome do Cliente')
                            ->placeholder('João Silva')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('telefone')
                            ->label('WhatsApp')
                            ->placeholder('(11) 99999-9999')
                            ->tel()
                            ->mask('(99) 99999-9999')
                            ->required(),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->placeholder('cliente@email.com'),

                        TextInput::make('cidade')
                            ->label('Cidade')
                            ->placeholder('São Paulo')
                            ->required(),

                        TextInput::make('estado')
                            ->label('Estado')
                            ->placeholder('SP')
                            ->maxLength(2)
                            ->default('SP'),

                        // DADOS DO ORÇAMENTO
                        Select::make('tipo_servico')
                            ->label('Tipo de Serviço')
                            ->options([
                                \App\Enums\ServiceType::Higienizacao->value => '🧼 Higienização',
                                \App\Enums\ServiceType::Impermeabilizacao->value => '💧 Impermeabilização',
                                \App\Enums\ServiceType::Combo->value => '🎯 Combo Completo',
                                \App\Enums\ServiceType::Outro->value => '🔧 Outro',
                            ])
                            ->required()
                            ->native(false),

                        Select::make('etapa_inicial')
                            ->label('Etapa Inicial')
                            ->options([
                                'novo' => '🌟 Novo Lead',
                                'contato_realizado' => '💬 Contato Feito',
                                'agendado' => '📅 Agendado',
                            ])
                            ->default('novo')
                            ->required()
                            ->native(false),

                        TextInput::make('valor_estimado')
                            ->label('Valor Estimado')
                            ->numeric()
                            ->prefix('R$')
                            ->placeholder('0,00')
                            ->columnSpan(2),

                        Textarea::make('observacoes')
                            ->label('Observações Iniciais')
                            ->placeholder('Detalhes da conversa, necessidades específicas...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                ])
                ->action(function (array $data): void {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
                        // 1. Criar ou buscar cliente
                        $cliente = Cadastro::firstOrCreate(
                            ['telefone' => $data['telefone']],
                            [
                                'nome' => $data['nome_cliente'],
                                'email' => $data['email'] ?? null,
                                'cidade' => $data['cidade'],
                                'estado' => $data['estado'] ?? 'SP',
                                'tipo' => 'cliente',
                                'origem' => 'crm_funil',
                            ]
                        );

                        // 2. Criar orçamento
                        $orcamento = Orcamento::create([
                            'numero' => Orcamento::gerarNumeroOrcamento(),
                            'cadastro_id' => $cliente->id,
                            'data_orcamento' => now(),
                            'data_validade' => now()->addDays(15),
                            'status' => 'rascunho',
                            'etapa_funil' => $data['etapa_inicial'],
                            'tipo_servico' => $data['tipo_servico'],
                            'valor_total' => (float) ($data['valor_estimado'] ?? 0),
                            'observacoes' => $data['observacoes'] ?? "Lead capturado via CRM Funil.\n\nContato: {$data['telefone']}",
                        ]);

                        Notification::make()
                            ->title('✅ Lead Criado com Sucesso!')
                            ->body("Cliente: {$cliente->nome} | Orçamento: {$orcamento->numero}")
                            ->success()
                            ->duration(5000)
                            ->send();

                        $this->redirect(static::getUrl());
                    });
                }),

            // BOTÃO: ANALISAR LEADS PARADOS
            Action::make('analisarParados')
                ->label('Leads Parados')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('⚡ Análise de Leads Parados')
                ->modalDescription('Identificar orçamentos sem movimentação há mais de 7 dias')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('leads:alert-stalled');

                    Notification::make()
                        ->title('Análise Concluída')
                        ->body('Você será notificado sobre leads que precisam de atenção.')
                        ->success()
                        ->send();
                }),

            // BOTÃO: ESTATÍSTICAS
            Action::make('estatisticas')
                ->label('Estatísticas')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->modalHeading('📊 Estatísticas do Funil')
                ->modalContent(view('filament.pages.components.funil-stats', $this->getViewData()))
                ->modalWidth('5xl')
                ->modalCancelAction(false),
        ];
    }
}
