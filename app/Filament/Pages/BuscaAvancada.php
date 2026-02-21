<?php

namespace App\Filament\Pages;

use App\Models\Agenda;
use App\Models\Cadastro;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BuscaAvancada extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Busca Avançada';

    protected static ?string $title = 'Busca Avançada';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.busca-avancada';

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
                Section::make('Filtros de Busca')
                    ->description('Preencha os campos para buscar em todo o sistema')
                    ->schema([
                        TextInput::make('termo')
                            ->label('Termo de Busca')
                            ->placeholder('Digite nome, CPF, telefone, número do orçamento...')
                            ->prefixIcon('heroicon-o-magnifying-glass')
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([
                                Select::make('tipoFiltro')
                                    ->label('Módulo')
                                    ->options([
                                        'todos' => 'Todos',
                                        'cadastros' => 'Cadastros',
                                        'orcamentos' => 'Orçamentos',
                                        'ordem_servicos' => 'Ordens de Serviço',
                                        'financeiro' => 'Financeiro',
                                        'agenda' => 'Agenda',
                                        'produtos' => 'Produtos',
                                    ])
                                    ->default('todos'),

                                Select::make('statusFiltro')
                                    ->label('Status')
                                    ->options([
                                        '' => 'Todos',
                                        'pendente' => 'Pendente',
                                        'aprovado' => 'Aprovado',
                                        'concluido' => 'Concluído',
                                        'cancelado' => 'Cancelado',
                                        'pago' => 'Pago',
                                    ]),

                                DatePicker::make('dataInicio')
                                    ->label('Data Início'),

                                DatePicker::make('dataFim')
                                    ->label('Data Fim'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buscar')
                ->label('Buscar')
                ->icon('heroicon-o-magnifying-glass')
                ->action('buscar')
                ->color('primary'),

            Action::make('limpar')
                ->label('Limpar')
                ->icon('heroicon-o-x-mark')
                ->action('limpar')
                ->color('gray'),
        ];
    }

    public function buscar(): void
    {
        if (empty($this->termo) && empty($this->dataInicio) && empty($this->dataFim) && empty($this->statusFiltro)) {
            $this->resultados = collect();
            $this->totalResultados = 0;

            return;
        }

        $this->resultados = collect();

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

        $this->resultados = $this->aplicarOrdenacao($this->resultados);
        $this->totalResultados = $this->resultados->count();
    }

    private function aplicarOrdenacao(Collection $resultados): Collection
    {
        return match ($this->ordenacao) {
            'antigo' => $resultados->sortBy('data_raw'),
            'nome' => $resultados->sortBy('titulo'),
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
                    ->orWhere('documento', 'like', $termo)
                    ->orWhere('telefone', 'like', $termo)
                    ->orWhere('celular', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhere('logradouro', 'like', $termo)
                    ->orWhere('cidade', 'like', $termo);
            });
        }

        if ($this->dataInicio) {
            $query->whereDate('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->whereDate('created_at', '<=', $this->dataFim);
        }

        foreach ($query->limit(30)->get() as $cadastro) {
            $this->resultados->push([
                'tipo' => 'cadastro',
                'tipo_icon' => match ($cadastro->tipo) {
                    'cliente' => '👤',
                    'loja' => '🏬',
                    'vendedor' => '🧑‍💼',
                    default => '📋',
                },
                'tipo_label' => ucfirst($cadastro->tipo ?? 'Cadastro'),
                'tipo_color' => 'info',
                'id' => $cadastro->id,
                'titulo' => $cadastro->nome,
                'subtitulo' => implode(' • ', array_filter([$cadastro->telefone, $cadastro->cidade])),
                'descricao' => $cadastro->logradouro,
                'status' => $cadastro->ativo ? 'Ativo' : 'Inativo',
                'status_color' => $cadastro->ativo ? 'success' : 'gray',
                'data' => $cadastro->created_at?->format('d/m/Y'),
                'data_raw' => $cadastro->created_at,
                'view_url' => route('filament.admin.resources.cadastros.edit', ['record' => $cadastro->id]),
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
                    ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', $termo));
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }
        if ($this->dataInicio) {
            $query->whereDate('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->whereDate('created_at', '<=', $this->dataFim);
        }

        foreach ($query->limit(30)->get() as $orcamento) {
            $this->resultados->push([
                'tipo' => 'orcamento',
                'tipo_icon' => '📋',
                'tipo_label' => 'Orçamento',
                'tipo_color' => 'warning',
                'id' => $orcamento->id,
                'titulo' => "Orçamento #{$orcamento->numero}",
                'subtitulo' => implode(' • ', array_filter([
                    $orcamento->cliente?->nome,
                    'R$ ' . number_format($orcamento->valor_efetivo, 2, ',', '.'),
                ])),
                'descricao' => $orcamento->descricao_servico,
                'status' => ucfirst($orcamento->status ?? 'pendente'),
                'status_color' => match ($orcamento->status) {
                    'aprovado' => 'success',
                    'cancelado' => 'danger',
                    default => 'warning',
                },
                'data' => $orcamento->created_at?->format('d/m/Y'),
                'data_raw' => $orcamento->created_at,
                'view_url' => route('filament.admin.resources.orcamentos.edit', ['record' => $orcamento->id]),
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
                    ->orWhereHas('cliente', fn($c) => $c->where('nome', 'like', $termo));
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }
        if ($this->dataInicio) {
            $query->whereDate('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->whereDate('created_at', '<=', $this->dataFim);
        }

        foreach ($query->limit(30)->get() as $os) {
            $this->resultados->push([
                'tipo' => 'ordem_servico',
                'tipo_icon' => '🛠️',
                'tipo_label' => 'Ordem de Serviço',
                'tipo_color' => 'success',
                'id' => $os->id,
                'titulo' => "OS #{$os->numero_os}",
                'subtitulo' => implode(' • ', array_filter([
                    $os->cliente?->nome,
                    'R$ ' . number_format($os->valor_total ?? 0, 2, ',', '.'),
                ])),
                'descricao' => $os->descricao_servico,
                'status' => ucfirst(str_replace('_', ' ', $os->status ?? 'pendente')),
                'status_color' => match ($os->status) {
                    'concluida', 'finalizada' => 'success',
                    'cancelada' => 'danger',
                    default => 'warning',
                },
                'data' => $os->created_at?->format('d/m/Y'),
                'data_raw' => $os->created_at,
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
                    ->orWhere('observacoes', 'like', $termo);
            });
        }

        if ($this->statusFiltro) {
            $query->where('status', $this->statusFiltro);
        }
        if ($this->dataInicio) {
            $query->whereDate('data', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->whereDate('data', '<=', $this->dataFim);
        }

        foreach ($query->limit(30)->get() as $financeiro) {
            $this->resultados->push([
                'tipo' => 'financeiro',
                'tipo_icon' => $financeiro->tipo === 'entrada' ? '💵' : '💸',
                'tipo_label' => 'Financeiro',
                'tipo_color' => $financeiro->tipo === 'entrada' ? 'success' : 'danger',
                'id' => $financeiro->id,
                'titulo' => $financeiro->descricao,
                'subtitulo' => 'R$ ' . number_format($financeiro->valor ?? 0, 2, ',', '.'),
                'descricao' => $financeiro->observacoes,
                'status' => ucfirst($financeiro->status ?? 'pendente'),
                'status_color' => match ($financeiro->status) {
                    'pago' => 'success',
                    'cancelado' => 'danger',
                    default => 'warning',
                },
                'data' => $financeiro->data?->format('d/m/Y'),
                'data_raw' => $financeiro->data,
                'view_url' => route('filament.admin.resources.financeiros.edit', ['record' => $financeiro->id]),
                'edit_url' => route('filament.admin.resources.financeiros.edit', ['record' => $financeiro->id]),
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

        if ($this->dataInicio) {
            $query->whereDate('data_hora_inicio', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->whereDate('data_hora_fim', '<=', $this->dataFim);
        }

        foreach ($query->limit(30)->get() as $agenda) {
            $this->resultados->push([
                'tipo' => 'agenda',
                'tipo_icon' => '📅',
                'tipo_label' => 'Agenda',
                'tipo_color' => 'primary',
                'id' => $agenda->id,
                'titulo' => $agenda->titulo,
                'subtitulo' => implode(' • ', array_filter([
                    $agenda->data_hora_inicio?->format('d/m/Y H:i'),
                    $agenda->local,
                ])),
                'descricao' => $agenda->descricao,
                'status' => ucfirst($agenda->status ?? 'agendado'),
                'status_color' => match ($agenda->status) {
                    'concluido' => 'success',
                    'cancelado' => 'danger',
                    default => 'info',
                },
                'data' => $agenda->data_hora_inicio?->format('d/m/Y'),
                'data_raw' => $agenda->data_hora_inicio,
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
                    ->orWhere('descricao', 'like', $termo);
            });
        }

        foreach ($query->limit(30)->get() as $produto) {
            $this->resultados->push([
                'tipo' => 'produto',
                'tipo_icon' => '📦',
                'tipo_label' => 'Produto',
                'tipo_color' => 'gray',
                'id' => $produto->id,
                'titulo' => $produto->nome,
                'subtitulo' => "Estoque: {$produto->estoque_atual}",
                'descricao' => $produto->descricao,
                'status' => ($produto->estoque_atual ?? 0) > 0 ? 'Em estoque' : 'Sem estoque',
                'status_color' => ($produto->estoque_atual ?? 0) > 0 ? 'success' : 'danger',
                'data' => $produto->created_at?->format('d/m/Y'),
                'data_raw' => $produto->created_at,
                'view_url' => route('filament.admin.resources.produtos.edit', ['record' => $produto->id]),
                'edit_url' => route('filament.admin.resources.produtos.edit', ['record' => $produto->id]),
            ]);
        }
    }

    public function limpar(): void
    {
        $this->reset(['termo', 'tipoFiltro', 'statusFiltro', 'ordenacao', 'dataInicio', 'dataFim']);
        $this->resultados = collect();
        $this->totalResultados = 0;
    }
}
