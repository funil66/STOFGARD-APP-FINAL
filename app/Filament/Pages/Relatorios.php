<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\Financeiro;
use App\Models\OrdemServico;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Relatorios extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?string $title = 'Relatórios e Análises';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.relatorios';

    public ?array $data = [];

    public $relatorioSelecionado = 'servicos';

    public $periodo = 'mes_atual';

    public $dataInicio = null;

    public $dataFim = null;

    public $dadosRelatorio = [];

    public function mount(): void
    {
        $this->dataInicio = now()->startOfMonth()->format('Y-m-d');
        $this->dataFim = now()->endOfMonth()->format('Y-m-d');
        $this->form->fill([
            'relatorio' => $this->relatorioSelecionado,
            'periodo' => $this->periodo,
            'data_inicio' => $this->dataInicio,
            'data_fim' => $this->dataFim,
        ]);
        $this->gerarRelatorio();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros do Relatório')
                    ->schema([
                        Select::make('relatorio')
                            ->label('Tipo de Relatório')
                            ->options([
                                'servicos' => '📋 Serviços Realizados',
                                'financeiro' => '💰 Financeiro (Receitas e Despesas)',
                                'clientes' => '👥 Análise de Clientes',
                                'estoque' => '📦 Movimentação de Estoque',
                                'comissoes' => '💵 Comissões de Parceiros',
                            ])
                            ->default('servicos')
                            ->live()
                            ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                        Select::make('periodo')
                            ->label('Período')
                            ->options([
                                'hoje' => 'Hoje',
                                'semana_atual' => 'Esta Semana',
                                'mes_atual' => 'Este Mês',
                                'mes_anterior' => 'Mês Anterior',
                                'ultimos_30' => 'Últimos 30 Dias',
                                'ultimos_90' => 'Últimos 90 Dias',
                                'ano_atual' => 'Este Ano',
                                'personalizado' => 'Período Personalizado',
                            ])
                            ->default('mes_atual')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->aplicarPeriodo($state);
                                $this->gerarRelatorio();
                            }),

                        DatePicker::make('data_inicio')
                            ->label('Data Início')
                            ->visible(fn ($get) => $get('periodo') === 'personalizado')
                            ->live()
                            ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                        DatePicker::make('data_fim')
                            ->label('Data Fim')
                            ->visible(fn ($get) => $get('periodo') === 'personalizado')
                            ->live()
                            ->afterStateUpdated(fn () => $this->gerarRelatorio()),

                        Select::make('cadastro_id')
                            ->label('Cadastro')
                            ->searchable()
                            ->options(function () {
                                $clientes = \App\Models\Cliente::orderBy('nome')->get()->mapWithKeys(fn($c) => ['cliente_'.$c->id => $c->nome]);
                                $parceiros = \App\Models\Parceiro::orderBy('nome')->get()->mapWithKeys(fn($p) => ['parceiro_'.$p->id => $p->nome]);
                                return $clientes->merge($parceiros)->toArray();
                            })
                            ->placeholder('Todos')
                            ->visible(fn ($get) => in_array($get('relatorio'), ['servicos', 'financeiro', 'clientes']))
                            ->reactive()
                            ->afterStateUpdated(fn () => $this->gerarRelatorio()),
                    ])
                    ->columns(4),
            ])
            ->statePath('data');
    }

    private function aplicarPeriodo($periodo): void
    {
        $this->periodo = $periodo;

        switch ($periodo) {
            case 'hoje':
                $this->dataInicio = now()->startOfDay()->format('Y-m-d');
                $this->dataFim = now()->endOfDay()->format('Y-m-d');
                break;
            case 'semana_atual':
                $this->dataInicio = now()->startOfWeek()->format('Y-m-d');
                $this->dataFim = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'mes_atual':
                $this->dataInicio = now()->startOfMonth()->format('Y-m-d');
                $this->dataFim = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'mes_anterior':
                $this->dataInicio = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dataFim = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'ultimos_30':
                $this->dataInicio = now()->subDays(30)->format('Y-m-d');
                $this->dataFim = now()->format('Y-m-d');
                break;
            case 'ultimos_90':
                $this->dataInicio = now()->subDays(90)->format('Y-m-d');
                $this->dataFim = now()->format('Y-m-d');
                break;
            case 'ano_atual':
                $this->dataInicio = now()->startOfYear()->format('Y-m-d');
                $this->dataFim = now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function gerarRelatorio(): void
    {
        $relatorio = $this->form->getState()['relatorio'] ?? 'servicos';

        $this->dadosRelatorio = match ($relatorio) {
            'servicos' => $this->relatorioServicos(),
            'financeiro' => $this->relatorioFinanceiro(),
            'clientes' => $this->relatorioClientes(),
            'estoque' => $this->relatorioEstoque(),
            'comissoes' => $this->relatorioComissoes(),
            default => [],
        };
    }

    private function relatorioServicos(): array
    {
        $query = OrdemServico::whereBetween('created_at', [
            Carbon::parse($this->dataInicio)->startOfDay(),
            Carbon::parse($this->dataFim)->endOfDay(),
        ]);

        $cadastro = $this->form->getState()['cadastro_id'] ?? null;
        if ($cadastro) {
            $query->where('cadastro_id', $cadastro);
        }

        $servicos = $query->get();

        $total = $servicos->count();
        $valor_total = $servicos->sum('valor_total');

        return [
            'total' => $total,
            'concluidos' => $servicos->where('status', 'concluido')->count(),
            'em_andamento' => $servicos->whereIn('status', ['agendado', 'em_execucao'])->count(),
            'cancelados' => $servicos->where('status', 'cancelado')->count(),
            'valor_total' => $valor_total,
            'ticket_medio' => $total > 0 ? ($valor_total / $total) : 0,
            'servicos' => $servicos->sortByDesc('created_at')->take(10),
        ];
    }

    private function relatorioFinanceiro(): array
    {
        $query = Financeiro::whereBetween('data_vencimento', [
            Carbon::parse($this->dataInicio)->startOfDay(),
            Carbon::parse($this->dataFim)->endOfDay(),
        ]);

        $cadastro = $this->form->getState()['cadastro_id'] ?? null;
        if ($cadastro) {
            $query->where('cadastro_id', $cadastro);
        }

        $financeiro = $query->get();

        $receitas = $financeiro->where('tipo', 'receita');
        $despesas = $financeiro->where('tipo', 'despesa');

        return [
            'receitas_total' => $receitas->sum('valor'),
            'receitas_pagas' => $receitas->where('status', 'pago')->sum('valor'),
            'receitas_pendentes' => $receitas->where('status', 'pendente')->sum('valor'),
            'despesas_total' => $despesas->sum('valor'),
            'despesas_pagas' => $despesas->where('status', 'pago')->sum('valor'),
            'despesas_pendentes' => $despesas->where('status', 'pendente')->sum('valor'),
            'saldo' => $receitas->where('status', 'pago')->sum('valor') - $despesas->where('status', 'pago')->sum('valor'),
            'transacoes' => $financeiro->sortByDesc('data_vencimento')->take(15),
        ];
    }

    private function relatorioClientes(): array
    {
        // Usar contagens com withCount e respeitar filtro de cadastro (se selecionado)
        $cadastroFiltro = $this->form->getState()['cadastro_id'] ?? null;

        $clientes = \App\Models\Cliente::withCount('ordensServico')->get();
        $parceiros = \App\Models\Parceiro::withCount('ordensServico')->get();

        if ($cadastroFiltro) {
            if (str_starts_with($cadastroFiltro, 'cliente_')) {
                $id = (int) str_replace('cliente_', '', $cadastroFiltro);
                $clientes = $clientes->where('id', $id)->values();
                $parceiros = collect();
            } elseif (str_starts_with($cadastroFiltro, 'parceiro_')) {
                $id = (int) str_replace('parceiro_', '', $cadastroFiltro);
                $parceiros = $parceiros->where('id', $id)->values();
                $clientes = collect();
            }
        }

        $total = $clientes->count() + $parceiros->count();

        $novos_periodo = $clientes->whereBetween('created_at', [
            Carbon::parse($this->dataInicio)->startOfDay(),
            Carbon::parse($this->dataFim)->endOfDay(),
        ])->count() + $parceiros->whereBetween('created_at', [
            Carbon::parse($this->dataInicio)->startOfDay(),
            Carbon::parse($this->dataFim)->endOfDay(),
        ])->count();

        $com_servicos = $clientes->where('ordens_servico_count', '>', 0)->count() +
            $parceiros->where('ordens_servico_count', '>', 0)->count();

        $inativos = $clientes->where('ordens_servico_count', 0)->count() +
            $parceiros->where('ordens_servico_count', 0)->count();

        // Top por quantidade de serviços - transformar em arrays para evitar problems de serialização no Livewire
        $top_unificados = $clientes->concat($parceiros)
            ->sortByDesc('ordens_servico_count')
            ->take(10)
            ->map(fn($m) => [
                'nome' => $m->nome,
                'tipo' => $m instanceof Cliente ? 'Cliente' : (ucfirst($m->tipo ?? 'Parceiro')),
                'total_servicos' => $m->ordens_servico_count,
                'telefone' => $m->celular ?? $m->telefone ?? null,
            ])
            ->values();

        return [
            'total' => $total,
            'novos_periodo' => $novos_periodo,
            'com_servicos' => $com_servicos,
            'inativos' => $inativos,
            'top_clientes' => $top_unificados,
        ];
    }

    private function relatorioEstoque(): array
    {
        return [
            'mensagem' => 'Relatório de movimentação de estoque em desenvolvimento',
        ];
    }

    private function relatorioComissoes(): array
    {
        return [
            'mensagem' => 'Relatório de comissões em desenvolvimento',
        ];
    }

    public function exportarPDF(): void
    {
        // Implementar exportação em PDF
        \Filament\Notifications\Notification::make()
            ->title('Exportação PDF')
            ->body('Funcionalidade em desenvolvimento')
            ->info()
            ->send();
    }

    public function exportarExcel(): void
    {
        // Implementar exportação em Excel
        \Filament\Notifications\Notification::make()
            ->title('Exportação Excel')
            ->body('Funcionalidade em desenvolvimento')
            ->info()
            ->send();
    }
}
