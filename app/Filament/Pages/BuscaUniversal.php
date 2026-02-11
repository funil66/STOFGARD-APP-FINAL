<?php

namespace App\Filament\Pages;

use App\Models\Agenda;
use App\Models\Cadastro;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BuscaUniversal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Busca Universal';

    protected static ?string $title = 'Busca Universal';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.busca-universal';

    // Propriedades do formulário
    public ?string $termo = '';

    public ?string $tipoFiltro = 'todos';

    public ?string $statusFiltro = '';

    public ?string $ordenacao = 'recente';

    public ?string $dataInicio = null;

    public ?string $dataFim = null;

    // Resultados
    public Collection $resultados;

    public int $totalResultados = 0;

    public function mount(): void
    {
        $this->resultados = collect();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextInput::make('termo')
                            ->label('🔍 O que você está procurando?')
                            ->placeholder('Digite ID, nome, CPF, telefone, endereço...')
                            ->suffixIcon('heroicon-o-magnifying-glass')
                            ->autofocus()
                            ->columnSpan(4)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn() => $this->buscar()),

                        Select::make('tipoFiltro')
                            ->label('📁 Módulo')
                            ->options([
                                'todos' => '🌐 Todos',
                                'cadastros' => '👥 Cadastros',
                                'orcamentos' => '📋 Orçamentos',
                                'ordem_servicos' => '🛠️ Ordens de Serviço',
                                'financeiro' => '💰 Financeiro',
                                'agenda' => '📅 Agenda',
                                'produtos' => '📦 Produtos',
                            ])
                            ->default('todos')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn() => $this->buscar()),

                        Select::make('statusFiltro')
                            ->label('📊 Status')
                            ->options([
                                '' => 'Todos',
                                'pendente' => '⏳ Pendente',
                                'aprovado' => '✅ Aprovado',
                                'concluido' => '✔️ Concluído',
                                'cancelado' => '❌ Cancelado',
                                'pago' => '💵 Pago',
                                'aberta' => '🔓 Aberta',
                                'agendado' => '📅 Agendado',
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn() => $this->buscar()),

                        Select::make('ordenacao')
                            ->label('📶 Ordenar por')
                            ->options([
                                'recente' => '🕐 Mais recente',
                                'antigo' => '📅 Mais antigo',
                                'nome' => '🔤 Nome A-Z',
                                'valor_desc' => '💰 Maior valor',
                                'valor_asc' => '💵 Menor valor',
                            ])
                            ->default('recente')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn() => $this->buscar()),

                        DatePicker::make('dataInicio')
                            ->label('📆 De')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(2),

                        DatePicker::make('dataFim')
                            ->label('📆 Até')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public function buscar(): void
    {
        if (empty($this->termo) && empty($this->dataInicio) && empty($this->dataFim) && empty($this->statusFiltro)) {
            $this->resultados = collect();
            $this->totalResultados = 0;

            return;
        }

        $this->resultados = collect();

        // Buscar em cada módulo conforme filtro
        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'cadastros') {
            $this->buscarCadastros();
        }

        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'orcamentos') {
            $this->buscarOrcamentos();
        }

        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'ordem_servicos') {
            $this->buscarOrdemServicos();
        }

        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'financeiro') {
            $this->buscarFinanceiro();
        }

        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'agenda') {
            $this->buscarAgenda();
        }

        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'produtos') {
            $this->buscarProdutos();
        }

        // Aplicar ordenação
        $this->resultados = $this->aplicarOrdenacao($this->resultados);

        $this->totalResultados = $this->resultados->count();
    }

    private function aplicarOrdenacao(Collection $resultados): Collection
    {
        return match ($this->ordenacao) {
            'antigo' => $resultados->sortBy('data_raw'),
            'nome' => $resultados->sortBy('titulo'),
            'valor_desc' => $resultados->sortByDesc('valor_raw'),
            'valor_asc' => $resultados->sortBy('valor_raw'),
            default => $resultados->sortByDesc('data_raw'),
        };
    }

    private function buscarCadastros(): void
    {
        $query = Cadastro::query();

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('nome', 'like', $termo)
                    ->orWhere('cpf_cnpj', 'like', $termo)
                    ->orWhere('telefone', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhere('logradouro', 'like', $termo)
                    ->orWhere('bairro', 'like', $termo)
                    ->orWhere('cidade', 'like', $termo);
            });
        }

        if ($this->dataInicio) {
            $query->where('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('created_at', '<=', $this->dataFim);
        }

        $cadastros = $query->limit(30)->get();

        foreach ($cadastros as $cadastro) {
            $tipoIcon = match ($cadastro->tipo) {
                'cliente' => '👤',
                'loja' => '🏬',
                'vendedor' => '🧑‍💼',
                default => '📋',
            };
            $tipoLabel = match ($cadastro->tipo) {
                'cliente' => 'Cliente',
                'loja' => 'Loja',
                'vendedor' => 'Vendedor',
                default => 'Cadastro',
            };

            $this->resultados->push([
                'tipo' => 'cadastro',
                'tipo_icon' => $tipoIcon,
                'tipo_label' => "{$tipoIcon} {$tipoLabel}",
                'tipo_color' => match ($cadastro->tipo) {
                    'cliente' => 'info',
                    'loja' => 'primary',
                    'vendedor' => 'warning',
                    default => 'gray',
                },
                'id' => $cadastro->id,
                'titulo' => $cadastro->nome,
                'subtitulo' => $this->formatarSubtitulo([
                    $cadastro->telefone,
                    $cadastro->cpf_cnpj,
                    $cadastro->cidade,
                ]),
                'descricao' => trim(implode(', ', array_filter([
                    $cadastro->logradouro,
                    $cadastro->bairro,
                ]))),
                'status' => $cadastro->ativo ? 'Ativo' : 'Inativo',
                'status_color' => $cadastro->ativo ? 'success' : 'gray',
                'data' => $cadastro->created_at?->format('d/m/Y'),
                'data_raw' => $cadastro->created_at,
                'valor_raw' => 0,
                'view_url' => route('filament.admin.resources.cadastros.view', ['record' => $cadastro->id]),
                'edit_url' => route('filament.admin.resources.cadastros.edit', ['record' => $cadastro->id]),
            ]);
        }
    }

    private function buscarOrcamentos(): void
    {
        $query = Orcamento::with('cliente');

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('numero', 'like', $termo)
                    ->orWhere('descricao_servico', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo)
                    ->orWhereHas('cliente', function ($clienteQuery) use ($termo) {
                        $clienteQuery->where('nome', 'like', $termo);
                    });
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }

        if ($this->dataInicio) {
            $query->where('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('created_at', '<=', $this->dataFim);
        }

        $orcamentos = $query->limit(30)->get();

        foreach ($orcamentos as $orcamento) {
            $statusColor = match ($orcamento->status) {
                'aprovado' => 'success',
                'rejeitado', 'cancelado' => 'danger',
                'enviado' => 'warning',
                default => 'gray',
            };

            $this->resultados->push([
                'tipo' => 'orcamento',
                'tipo_icon' => '📋',
                'tipo_label' => '📋 Orçamento',
                'tipo_color' => 'warning',
                'id' => $orcamento->id,
                'titulo' => "Orçamento #{$orcamento->numero}",
                'subtitulo' => $this->formatarSubtitulo([
                    $orcamento->cliente?->nome ?? 'Sem cliente',
                    'R$ ' . number_format($orcamento->valor_efetivo, 2, ',', '.'),
                ]),
                'descricao' => $orcamento->descricao_servico,
                'status' => ucfirst($orcamento->status ?? 'pendente'),
                'status_color' => $statusColor,
                'data' => $orcamento->created_at?->format('d/m/Y'),
                'data_raw' => $orcamento->created_at,
                'valor_raw' => $orcamento->valor_efetivo,
                'view_url' => route('filament.admin.resources.orcamentos.view', ['record' => $orcamento->id]),
                'edit_url' => route('filament.admin.resources.orcamentos.edit', ['record' => $orcamento->id]),
            ]);
        }
    }

    private function buscarOrdemServicos(): void
    {
        $query = OrdemServico::with('cliente');

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('numero_os', 'like', $termo)
                    ->orWhere('descricao_servico', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo)
                    ->orWhereHas('cliente', function ($clienteQuery) use ($termo) {
                        $clienteQuery->where('nome', 'like', $termo);
                    });
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }

        if ($this->dataInicio) {
            $query->where('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('created_at', '<=', $this->dataFim);
        }

        $ordemServicos = $query->limit(30)->get();

        foreach ($ordemServicos as $os) {
            $statusColor = match ($os->status) {
                'concluida', 'finalizada' => 'success',
                'cancelada' => 'danger',
                'em_andamento' => 'warning',
                default => 'info',
            };

            $this->resultados->push([
                'tipo' => 'ordem_servico',
                'tipo_icon' => '🛠️',
                'tipo_label' => '🛠️ Ordem de Serviço',
                'tipo_color' => 'success',
                'id' => $os->id,
                'titulo' => "OS #{$os->numero_os}",
                'subtitulo' => $this->formatarSubtitulo([
                    $os->cliente?->nome ?? 'Sem cliente',
                    'R$ ' . number_format($os->valor_total ?? 0, 2, ',', '.'),
                ]),
                'descricao' => $os->descricao_servico,
                'status' => ucfirst(str_replace('_', ' ', $os->status ?? 'pendente')),
                'status_color' => $statusColor,
                'data' => $os->created_at?->format('d/m/Y'),
                'data_raw' => $os->created_at,
                'valor_raw' => $os->valor_total ?? 0,
                'view_url' => route('filament.admin.resources.ordem-servicos.view', ['record' => $os->id]),
                'edit_url' => route('filament.admin.resources.ordem-servicos.edit', ['record' => $os->id]),
            ]);
        }
    }

    private function buscarFinanceiro(): void
    {
        $query = Financeiro::query();

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('descricao', 'like', $termo)
                    ->orWhere('categoria', 'like', $termo);
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }

        if ($this->dataInicio) {
            $query->where('data', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('data', '<=', $this->dataFim);
        }

        $financeiros = $query->limit(30)->get();

        foreach ($financeiros as $financeiro) {
            $icone = $financeiro->tipo === 'entrada' ? '💵' : '💸';
            $statusColor = match ($financeiro->status) {
                'pago' => 'success',
                'cancelado' => 'danger',
                'vencido' => 'danger',
                default => 'warning',
            };

            $this->resultados->push([
                'tipo' => 'financeiro',
                'tipo_icon' => $icone,
                'tipo_label' => "💰 {$icone} " . ucfirst($financeiro->tipo),
                'tipo_color' => $financeiro->tipo === 'entrada' ? 'success' : 'danger',
                'id' => $financeiro->id,
                'titulo' => $financeiro->descricao,
                'subtitulo' => $this->formatarSubtitulo([
                    'R$ ' . number_format($financeiro->valor ?? 0, 2, ',', '.'),
                    $financeiro->categoria,
                ]),
                'descricao' => $financeiro->observacoes,
                'status' => ucfirst($financeiro->status ?? 'pendente'),
                'status_color' => $statusColor,
                'data' => $financeiro->data?->format('d/m/Y'),
                'data_raw' => $financeiro->data,
                'valor_raw' => $financeiro->valor ?? 0,
                'view_url' => route('filament.admin.resources.financeiros.transacoes.view', ['record' => $financeiro->id]),
                'edit_url' => route('filament.admin.resources.financeiros.transacoes.edit', ['record' => $financeiro->id]),
            ]);
        }
    }

    private function buscarAgenda(): void
    {
        $query = Agenda::query();

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('titulo', 'like', $termo)
                    ->orWhere('descricao', 'like', $termo)
                    ->orWhere('local', 'like', $termo);
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }

        if ($this->dataInicio) {
            $query->where('data_hora_inicio', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('data_hora_fim', '<=', $this->dataFim);
        }

        $agendas = $query->limit(30)->get();

        foreach ($agendas as $agenda) {
            $statusColor = match ($agenda->status) {
                'concluido' => 'success',
                'cancelado' => 'danger',
                'em_andamento' => 'warning',
                default => 'info',
            };

            $this->resultados->push([
                'tipo' => 'agenda',
                'tipo_icon' => '📅',
                'tipo_label' => '📅 Agenda',
                'tipo_color' => 'primary',
                'id' => $agenda->id,
                'titulo' => $agenda->titulo,
                'subtitulo' => $this->formatarSubtitulo([
                    $agenda->data_hora_inicio?->format('d/m/Y H:i'),
                    $agenda->local,
                ]),
                'descricao' => $agenda->descricao,
                'status' => ucfirst(str_replace('_', ' ', $agenda->status ?? 'agendado')),
                'status_color' => $statusColor,
                'data' => $agenda->data_hora_inicio?->format('d/m/Y'),
                'data_raw' => $agenda->data_hora_inicio,
                'valor_raw' => 0,
                'view_url' => route('filament.admin.resources.agendas.view', ['record' => $agenda->id]),
                'edit_url' => route('filament.admin.resources.agendas.edit', ['record' => $agenda->id]),
            ]);
        }
    }

    private function buscarProdutos(): void
    {
        $query = Produto::query();

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('nome', 'like', $termo)
                    ->orWhere('descricao', 'like', $termo)
                    ->orWhere('categoria', 'like', $termo)
                    ->orWhere('codigo_barras', 'like', $termo);
            });
        }

        $produtos = $query->limit(30)->get();

        foreach ($produtos as $produto) {
            $estoqueStatus = ($produto->quantidade_estoque ?? 0) > 0 ? 'Em estoque' : 'Sem estoque';
            $estoqueColor = ($produto->quantidade_estoque ?? 0) > 0 ? 'success' : 'danger';

            $this->resultados->push([
                'tipo' => 'produto',
                'tipo_icon' => '📦',
                'tipo_label' => '📦 Produto',
                'tipo_color' => 'gray',
                'id' => $produto->id,
                'titulo' => $produto->nome,
                'subtitulo' => $this->formatarSubtitulo([
                    $produto->categoria,
                    "Estoque: {$produto->quantidade_estoque}",
                ]),
                'descricao' => $produto->descricao,
                'status' => $estoqueStatus,
                'status_color' => $estoqueColor,
                'data' => $produto->created_at?->format('d/m/Y'),
                'data_raw' => $produto->created_at,
                'valor_raw' => $produto->preco_venda ?? 0,
                'view_url' => route('filament.admin.resources.almoxarifado.produtos.view', ['record' => $produto->id]),
                'edit_url' => route('filament.admin.resources.almoxarifado.produtos.edit', ['record' => $produto->id]),
            ]);
        }
    }

    private function formatarSubtitulo(array $partes): string
    {
        return collect($partes)
            ->filter(fn($parte) => !empty($parte))
            ->implode(' • ');
    }

    public function limpar(): void
    {
        $this->reset(['termo', 'tipoFiltro', 'statusFiltro', 'ordenacao', 'dataInicio', 'dataFim']);
        $this->resultados = collect();
        $this->totalResultados = 0;
    }
}
