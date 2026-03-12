<?php

namespace App\Filament\Pages;

use App\Models\Cadastro;
use App\Models\Estoque;
use App\Models\Financeiro;
use App\Models\OrdemServico;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
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

    /**
     * Relatórios financeiros: requer acesso financeiro (dono ou flag acesso_financeiro).
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->temAcessoFinanceiro() ?? false;
    }

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
                                return Cadastro::orderBy('nome')->get()->mapWithKeys(fn ($c) => [$c->id => $c->nome])->toArray();
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

        $query = Cadastro::withCount('ordensServico');

        if ($cadastroFiltro) {
            $query->where('id', $cadastroFiltro);
        }

        $cadastros = $query->get();

        $total = $cadastros->count();

        $novos_periodo = $cadastros->whereBetween('created_at', [
            Carbon::parse($this->dataInicio)->startOfDay(),
            Carbon::parse($this->dataFim)->endOfDay(),
        ])->count();

        $com_servicos = $cadastros->where('ordens_servico_count', '>', 0)->count();

        $inativos = $cadastros->where('ordens_servico_count', 0)->count();

        // Top por quantidade de serviços - transformar em arrays para evitar problems de serialização no Livewire
        $top_unificados = $cadastros
            ->sortByDesc('ordens_servico_count')
            ->take(10)
            ->map(fn ($m) => [
                'nome' => $m->nome,
                'tipo' => ucfirst($m->tipo ?? 'Cadastro'),
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
        $estoques = Estoque::orderBy('item')->get();

        // Itens abaixo do mínimo
        $abaixoMinimo = $estoques->filter(fn ($e) => $e->isAbaixoDoMinimo())
            ->map(fn ($e) => [
                'id' => $e->id,
                'item' => $e->item,
                'quantidade' => (float) $e->quantidade,
                'unidade' => $e->unidade ?? 'un',
                'minimo_alerta' => (float) $e->minimo_alerta,
                'preco_interno' => (float) $e->preco_interno,
                'preco_venda' => (float) $e->preco_venda,
                'cor' => $e->cor,
                'local' => $e->localEstoque?->nome ?? 'Geral',
            ])->values();

        // Consumo no período (via pivot ordem_servico_estoque)
        $consumo = \DB::table('ordem_servico_estoque')
            ->join('estoques', 'estoques.id', '=', 'ordem_servico_estoque.estoque_id')
            ->join('ordens_servico', 'ordens_servico.id', '=', 'ordem_servico_estoque.ordem_servico_id')
            ->whereBetween('ordem_servico_estoque.created_at', [
                Carbon::parse($this->dataInicio)->startOfDay(),
                Carbon::parse($this->dataFim)->endOfDay(),
            ])
            ->select(
                'estoques.item',
                'estoques.unidade',
                \DB::raw('SUM(ordem_servico_estoque.quantidade_utilizada) as total_consumido'),
                \DB::raw('COUNT(DISTINCT ordem_servico_estoque.ordem_servico_id) as total_os'),
            )
            ->groupBy('estoques.id', 'estoques.item', 'estoques.unidade')
            ->orderByDesc('total_consumido')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'item' => $c->item,
                'unidade' => $c->unidade ?? 'un',
                'total_consumido' => (float) $c->total_consumido,
                'total_os' => (int) $c->total_os,
            ])->values();

        // Valor total em estoque
        $valorTotalInterno = $estoques->sum(fn ($e) => (float) $e->quantidade * (float) $e->preco_interno);
        $valorTotalVenda = $estoques->sum(fn ($e) => (float) $e->quantidade * (float) $e->preco_venda);

        return [
            'total_itens' => $estoques->count(),
            'total_abaixo_minimo' => $abaixoMinimo->count(),
            'valor_total_interno' => $valorTotalInterno,
            'valor_total_venda' => $valorTotalVenda,
            'abaixo_minimo' => $abaixoMinimo,
            'consumo_periodo' => $consumo,
        ];
    }

    private function relatorioComissoes(): array
    {
        $query = Financeiro::where('is_comissao', true)
            ->whereBetween('data', [
                Carbon::parse($this->dataInicio)->startOfDay(),
                Carbon::parse($this->dataFim)->endOfDay(),
            ]);

        $comissoes = $query->get();

        $totalPendente = $comissoes->where('comissao_paga', false)->sum('valor');
        $totalPago = $comissoes->where('comissao_paga', true)->sum('valor');

        // Agrupar por parceiro
        $porParceiro = $comissoes->groupBy('cadastro_id')
            ->map(function ($grupo) {
                $cadastro = $grupo->first()->cadastro;
                return [
                    'nome' => $cadastro?->nome ?? 'Desconhecido',
                    'tipo' => $cadastro?->tipo ?? 'parceiro',
                    'total' => (float) $grupo->sum('valor'),
                    'pago' => (float) $grupo->where('comissao_paga', true)->sum('valor'),
                    'pendente' => (float) $grupo->where('comissao_paga', false)->sum('valor'),
                    'qtd' => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        // Lista detalhada
        $lista = $comissoes->sortByDesc('data')->take(20)
            ->map(fn ($c) => [
                'data' => $c->data?->format('d/m/Y') ?? '-',
                'parceiro' => $c->cadastro?->nome ?? 'N/A',
                'descricao' => $c->descricao,
                'valor' => (float) $c->valor,
                'status' => $c->comissao_paga ? 'Paga' : 'Pendente',
                'data_pagamento' => $c->comissao_data_pagamento?->format('d/m/Y') ?? '-',
            ])->values();

        return [
            'total_geral' => (float) ($totalPago + $totalPendente),
            'total_pago' => (float) $totalPago,
            'total_pendente' => (float) $totalPendente,
            'qtd_comissoes' => $comissoes->count(),
            'por_parceiro' => $porParceiro,
            'lista' => $lista,
        ];
    }

    public function exportarPDF(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('PDF sendo gerado...')
            ->body('O download iniciará em instantes.')
            ->success()
            ->send();

        // Redirect to PDF generation route with current filters
        $this->redirect(route('relatorio.pdf', [
            'tipo' => $this->form->getState()['relatorio'] ?? 'servicos',
            'inicio' => $this->dataInicio,
            'fim' => $this->dataFim,
        ]));
    }

    public function exportarExcel()
    {
        $relatorio = $this->form->getState()['relatorio'] ?? 'servicos';
        $dados = $this->dadosRelatorio;

        // Build CSV content based on report type
        $csv = $this->gerarCSV($relatorio, $dados);

        $nomeArquivo = "relatorio_{$relatorio}_" . now()->format('Y-m-d') . '.csv';

        return Response::streamDownload(function () use ($csv) {
            echo $csv;
        }, $nomeArquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function gerarCSV(string $tipo, array $dados): string
    {
        $output = chr(0xEF) . chr(0xBB) . chr(0xBF); // UTF-8 BOM for Excel

        switch ($tipo) {
            case 'servicos':
                $output .= "Data;Cliente;Tipo;Status;Valor\n";
                if (!empty($dados['servicos'])) {
                    foreach ($dados['servicos'] as $s) {
                        $output .= implode(';', [
                            $s->created_at->format('d/m/Y'),
                            $s->cadastro?->nome ?? $s->cliente?->nome ?? 'N/A',
                            $s->tipo ?? '',
                            ucfirst($s->status),
                            number_format((float) $s->valor_total, 2, ',', '.'),
                        ]) . "\n";
                    }
                }
                break;

            case 'financeiro':
                $output .= "Data;Cadastro;Descrição;Tipo;Status;Valor\n";
                if (!empty($dados['transacoes'])) {
                    foreach ($dados['transacoes'] as $t) {
                        $output .= implode(';', [
                            $t->data_vencimento ? Carbon::parse($t->data_vencimento)->format('d/m/Y') : '',
                            $t->cadastro?->nome ?? 'N/A',
                            str_replace(';', ',', $t->descricao ?? ''),
                            ucfirst($t->tipo),
                            ucfirst($t->status),
                            number_format((float) $t->valor, 2, ',', '.'),
                        ]) . "\n";
                    }
                }
                break;

            case 'estoque':
                $output .= "Item;Quantidade;Unidade;Mínimo;Preço Interno;Preço Venda;Local;Status\n";
                if (!empty($dados['abaixo_minimo'])) {
                    foreach ($dados['abaixo_minimo'] as $e) {
                        $output .= implode(';', [
                            $e['item'],
                            number_format($e['quantidade'], 2, ',', '.'),
                            $e['unidade'],
                            number_format($e['minimo_alerta'], 2, ',', '.'),
                            number_format($e['preco_interno'], 2, ',', '.'),
                            number_format($e['preco_venda'], 2, ',', '.'),
                            $e['local'],
                            'ABAIXO DO MÍNIMO',
                        ]) . "\n";
                    }
                }
                if (!empty($dados['consumo_periodo'])) {
                    $output .= "\nConsumo no Período\nItem;Unidade;Total Consumido;Total OS\n";
                    foreach ($dados['consumo_periodo'] as $c) {
                        $output .= implode(';', [
                            $c['item'],
                            $c['unidade'],
                            number_format($c['total_consumido'], 2, ',', '.'),
                            $c['total_os'],
                        ]) . "\n";
                    }
                }
                break;

            case 'comissoes':
                $output .= "Parceiro;Total;Pago;Pendente;Qtd\n";
                if (!empty($dados['por_parceiro'])) {
                    foreach ($dados['por_parceiro'] as $p) {
                        $output .= implode(';', [
                            $p['nome'],
                            number_format($p['total'], 2, ',', '.'),
                            number_format($p['pago'], 2, ',', '.'),
                            number_format($p['pendente'], 2, ',', '.'),
                            $p['qtd'],
                        ]) . "\n";
                    }
                }
                break;

            case 'clientes':
                $output .= "#;Nome;Tipo;Telefone;Total Serviços\n";
                if (!empty($dados['top_clientes'])) {
                    foreach ($dados['top_clientes'] as $i => $c) {
                        $output .= implode(';', [
                            $i + 1,
                            $c['nome'] ?? 'N/A',
                            $c['tipo'] ?? '',
                            $c['telefone'] ?? '',
                            $c['total_servicos'] ?? 0,
                        ]) . "\n";
                    }
                }
                break;
        }

        return $output;
    }
}
