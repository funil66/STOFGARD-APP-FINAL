<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuscaAvancada extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string $view = 'filament.pages.busca-avancada';

    protected static ?string $navigationLabel = 'Busca Avançada';

    protected static ?string $title = 'Busca Avançada';

    protected static ?int $navigationSort = 999;

    public ?string $searchTerm = null;

    public ?string $searchTable = 'all';

    public array $results = [];

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('searchTerm')
                ->label('Termo de busca')
                ->placeholder('Digite qualquer coisa para buscar...')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $this->search()),

            Select::make('searchTable')
                ->label('Buscar em')
                ->options($this->getTableOptions())
                ->default('all')
                ->live()
                ->afterStateUpdated(fn () => $this->search()),
        ];
    }

    protected function getTableOptions(): array
    {
        return [
            'all' => 'Todos os módulos',
            'clientes' => 'Clientes',
            'ordem_servicos' => 'Ordens de Serviço',
            'agendas' => 'Agendas',
            'orcamentos' => 'Orçamentos',
            'financeiros' => 'Financeiro',
            'estoques' => 'Almoxarifado',
            'produtos' => 'Produtos',
            'tabela_precos' => 'Tabela de Preços',
            'whats_app_mensagems' => 'WhatsApp Mensagens',
            'parceiros' => 'Parceiros',
            'inventarios' => 'Inventários',
            'configuracaos' => 'Configurações',
        ];
    }

    public function search(): void
    {
        if (! $this->searchTerm || strlen($this->searchTerm) < 2) {
            $this->results = [];

            return;
        }

        $this->results = [];
        $searchTerm = '%'.$this->searchTerm.'%';

        $tables = $this->searchTable === 'all'
            ? array_keys(array_filter($this->getTableOptions(), fn ($key) => $key !== 'all', ARRAY_FILTER_USE_KEY))
            : [$this->searchTable];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);

            // Adiciona WHERE para cada coluna de texto
            $query->where(function ($q) use ($columns, $searchTerm, $table) {
                foreach ($columns as $column) {
                    $columnType = Schema::getColumnType($table, $column);
                    if (in_array($columnType, ['string', 'text'])) {
                        $q->orWhere($column, 'like', $searchTerm);
                    }
                }
            });

            $tableResults = $query->limit(10)->get();

            if ($tableResults->isNotEmpty()) {
                $this->results[$table] = [
                    'label' => $this->getTableOptions()[$table] ?? $table,
                    'data' => $tableResults->toArray(),
                    'count' => $tableResults->count(),
                ];
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
