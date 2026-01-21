<?php

namespace App\Filament\Pages;

use App\Models\Agenda;
use App\Models\Cliente;
use App\Models\Financeiro;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use Filament\Forms\Components\DatePicker;
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

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.busca-universal';

    // Propriedades do formulário
    public ?string $termo = '';

    public ?string $tipoFiltro = 'todos';

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
                TextInput::make('termo')
                    ->label('O que você está procurando?')
                    ->placeholder('Digite ID, nome, CPF, telefone, endereço...')
                    ->suffixIcon('heroicon-o-magnifying-glass')
                    ->autofocus()
                    ->columnSpanFull(),

                Select::make('tipoFiltro')
                    ->label('Buscar em')
                    ->options([
                        'todos' => '🌐 Todos os módulos',
                        'clientes' => '👤 Clientes',
                        'orcamentos' => '📋 Orçamentos',
                        'ordem_servicos' => '🛠️ Ordens de Serviço',
                        'financeiro' => '💰 Financeiro',
                        'agenda' => '📅 Agenda',
                        'produtos' => '📦 Produtos/Almoxarifado',
                    ])
                    ->default('todos')
                    ->native(false),

                DatePicker::make('dataInicio')
                    ->label('Data Início')
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                DatePicker::make('dataFim')
                    ->label('Data Fim')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
            ])
            ->columns(3);
    }

    public function buscar(): void
    {
        if (empty($this->termo) && empty($this->dataInicio) && empty($this->dataFim)) {
            $this->resultados = collect();
            $this->totalResultados = 0;

            return;
        }

        $this->resultados = collect();

        // Buscar em cada módulo conforme filtro
        if ($this->tipoFiltro === 'todos' || $this->tipoFiltro === 'clientes') {
            $this->buscarClientes();
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

        $this->totalResultados = $this->resultados->count();
    }

    private function buscarClientes(): void
    {
        // Buscar clientes
        $queryClientes = Cliente::query();
        if ($this->termo) {
            $queryClientes->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('nome', 'like', $termo)
                    ->orWhere('cpf_cnpj', 'like', $termo)
                    ->orWhere('telefone', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhere('endereco', 'like', $termo)
                    ->orWhere('bairro', 'like', $termo)
                    ->orWhere('cidade', 'like', $termo);
            });
        }
        if ($this->dataInicio) {
            $queryClientes->where('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $queryClientes->where('created_at', '<=', $this->dataFim);
        }
        $clientes = $queryClientes->limit(50)->get();
        foreach ($clientes as $cliente) {
            $this->resultados->push([
                'tipo' => 'cliente',
                'tipo_label' => '👤 Cliente',
                'tipo_color' => 'info',
                'id' => 'cliente_' . $cliente->id,
                'titulo' => $cliente->nome,
                'subtitulo' => $this->formatarSubtitulo([
                    $cliente->telefone,
                    $cliente->cpf_cnpj,
                    $cliente->cidade,
                ]),
                'descricao' => $cliente->endereco,
                'data' => $cliente->created_at?->format('d/m/Y'),
                'url' => url('/cadastros/'.($cliente->uuid ?? $cliente->id)),
            ]);
        }
        // Buscar parceiros (loja/vendedor)
        $queryParceiros = \App\Models\Parceiro::query();
        if ($this->termo) {
            $queryParceiros->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('nome', 'like', $termo)
                    ->orWhere('cnpj_cpf', 'like', $termo)
                    ->orWhere('telefone', 'like', $termo)
                    ->orWhere('email', 'like', $termo)
                    ->orWhere('bairro', 'like', $termo)
                    ->orWhere('cidade', 'like', $termo);
            });
        }
        if ($this->dataInicio) {
            $queryParceiros->where('created_at', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $queryParceiros->where('created_at', '<=', $this->dataFim);
        }
        $parceiros = $queryParceiros->limit(50)->get();
        foreach ($parceiros as $parceiro) {
            $tipo = $parceiro->tipo === 'loja' ? 'Loja' : 'Vendedor';
            $this->resultados->push([
                'tipo' => $parceiro->tipo,
                'tipo_label' => $tipo === 'Loja' ? '🏬 Loja' : '🧑‍💼 Vendedor',
                'tipo_color' => $tipo === 'Loja' ? 'primary' : 'secondary',
                'id' => 'parceiro_' . $parceiro->id,
                'titulo' => $parceiro->nome,
                'subtitulo' => $this->formatarSubtitulo([
                    $parceiro->telefone,
                    $parceiro->cnpj_cpf,
                    $parceiro->cidade,
                ]),
                'descricao' => $parceiro->endereco_completo,
                'data' => $parceiro->created_at?->format('d/m/Y'),
                'url' => url('/cadastros/'.($parceiro->uuid ?? $parceiro->id)),
            ]);
        }
    }

    private function buscarOrcamentos(): void
    {
        $query = Orcamento::with(['cliente', 'parceiro']);

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('id', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo)
                    ->orWhereHas('cliente', function ($clienteQuery) use ($termo) {
                        $clienteQuery->where('nome', 'like', $termo);
                    })
                    ->orWhereHas('parceiro', function ($parceiroQuery) use ($termo) {
                        $parceiroQuery->where('nome', 'like', $termo);
                    });
            });
        }

        if ($this->dataInicio) {
            $query->where('data', '>=', $this->dataInicio);
        }

        if ($this->dataFim) {
            $query->where('data', '<=', $this->dataFim);
        }

        $orcamentos = $query->limit(50)->get();

        foreach ($orcamentos as $orcamento) {
            $nomeCadastro = $orcamento->cadastro?->nome ?? 'Sem cadastro';

            $this->resultados->push([
                'tipo' => 'orcamento',
                'tipo_label' => '📋 Orçamento',
                'tipo_color' => 'warning',
                'id' => $orcamento->id,
                'titulo' => "Orçamento #{$orcamento->id}",
                'subtitulo' => $this->formatarSubtitulo([
                    $nomeCadastro,
                    'R$ '.number_format($orcamento->valor_total, 2, ',', '.'),
                    ucfirst($orcamento->status ?? 'pendente'),
                ]),
                'descricao' => $orcamento->observacoes,
                'data' => $orcamento->data?->format('d/m/Y'),
                'url' => admin_resource_route('filament.admin.resources.orcamentos.edit', '/admin/orcamentos/{id}/edit', ['record' => $orcamento->id]),
            ]);
        }
    }

    private function buscarOrdemServicos(): void
    {
        $query = OrdemServico::with('orcamento');

        if ($this->termo) {
            $query->where(function ($q) {
                $termo = "%{$this->termo}%";
                $q->where('id', 'like', $termo)
                    ->orWhere('observacoes', 'like', $termo)
                    ->orWhereHas('orcamento', function ($orcQuery) use ($termo) {
                        $orcQuery->whereHas('cliente', function ($clienteQuery) use ($termo) {
                            $clienteQuery->where('nome', 'like', $termo);
                        })->orWhereHas('parceiro', function ($parceiroQuery) use ($termo) {
                            $parceiroQuery->where('nome', 'like', $termo);
                        });
                    });
            });
        }

        if ($this->dataInicio) {
            $query->where('data_inicio', '>=', $this->dataInicio);
        }

        if ($this->dataFim) {
            $query->where('data_conclusao', '<=', $this->dataFim);
        }

        $ordemServicos = $query->limit(50)->get();

        foreach ($ordemServicos as $os) {
            $cliente = $os->orcamento->cadastro ?? null;

            $this->resultados->push([
                'tipo' => 'ordem_servico',
                'tipo_label' => '🛠️ Ordem de Serviço',
                'tipo_color' => 'success',
                'id' => $os->id,
                'titulo' => "OS #{$os->id}",
                'subtitulo' => $this->formatarSubtitulo([
                    $cliente?->nome ?? 'Sem cliente',
                    ucfirst($os->status ?? 'pendente'),
                ]),
                'descricao' => $os->observacoes,
                'data' => $os->data_inicio?->format('d/m/Y'),
                'url' => admin_resource_route('filament.admin.resources.ordem-servicos.edit', '/admin/ordem-servicos/{id}/edit', ['record' => $os->id]),
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
        if ($this->dataInicio) {
            $query->where('data', '>=', $this->dataInicio);
        }
        if ($this->dataFim) {
            $query->where('data', '<=', $this->dataFim);
        }
        $financeiros = $query->limit(50)->get();
        foreach ($financeiros as $financeiro) {
            $icone = $financeiro->tipo === 'entrada' ? '💵' : '💸';
            $nomeCadastro = $financeiro->cadastro->nome ?? ($financeiro->cliente->nome ?? 'N/A');
            $this->resultados->push([
                'tipo' => 'financeiro',
                'tipo_label' => "💰 Financeiro {$icone}",
                'tipo_color' => $financeiro->tipo === 'entrada' ? 'success' : 'danger',
                'id' => $financeiro->id,
                'titulo' => $financeiro->descricao,
                'subtitulo' => $this->formatarSubtitulo([
                    $nomeCadastro,
                    'R$ '.number_format($financeiro->valor, 2, ',', '.'),
                    ucfirst($financeiro->status ?? 'pendente'),
                ]),
                'descricao' => $financeiro->categoria,
                'data' => $financeiro->data?->format('d/m/Y'),
                'url' => admin_resource_route('filament.admin.resources.financeiros.edit', '/admin/financeiros/{id}/edit', ['record' => $financeiro->id]),
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
            $query->where('data_hora_inicio', '>=', $this->dataInicio);
        }

        if ($this->dataFim) {
            $query->where('data_hora_fim', '<=', $this->dataFim);
        }

        $agendas = $query->limit(50)->get();

        foreach ($agendas as $agenda) {
            $this->resultados->push([
                'tipo' => 'agenda',
                'tipo_label' => '📅 Agenda',
                'tipo_color' => 'primary',
                'id' => $agenda->id,
                'titulo' => $agenda->titulo,
                'subtitulo' => $this->formatarSubtitulo([
                    $agenda->data_hora_inicio?->format('d/m/Y H:i'),
                    $agenda->local,
                ]),
                'descricao' => $agenda->descricao,
                'data' => $agenda->data_hora_inicio?->format('d/m/Y'),
                'url' => admin_resource_route('filament.admin.resources.agendas.edit', '/admin/agendas/{id}/edit', ['record' => $agenda->id]),
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

        $produtos = $query->limit(50)->get();

        foreach ($produtos as $produto) {
            $this->resultados->push([
                'tipo' => 'produto',
                'tipo_label' => '📦 Produto',
                'tipo_color' => 'gray',
                'id' => $produto->id,
                'titulo' => $produto->nome,
                'subtitulo' => $this->formatarSubtitulo([
                    $produto->categoria,
                    "Estoque: {$produto->quantidade_estoque}",
                ]),
                'descricao' => $produto->descricao,
                'data' => $produto->created_at?->format('d/m/Y'),
                'url' => admin_resource_route('filament.admin.resources.produtos.edit', '/admin/produtos/{id}/edit', ['record' => $produto->id]),
            ]);
        }
    }

    private function formatarSubtitulo(array $partes): string
    {
        return collect($partes)
            ->filter(fn ($parte) => ! empty($parte))
            ->implode(' • ');
    }

    public function limpar(): void
    {
        $this->reset(['termo', 'tipoFiltro', 'dataInicio', 'dataFim']);
        $this->resultados = collect();
        $this->totalResultados = 0;
    }
}
