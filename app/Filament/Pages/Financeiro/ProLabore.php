<?php

namespace App\Filament\Pages\Financeiro;

use App\Models\Financeiro;
use App\Models\User;
use App\Services\ProLaboreCalculator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Livewire\Attributes\Computed;

class ProLabore extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.financeiro.pro-labore';

    protected static ?string $title = 'Distribuição de Pró-Labore';

    protected static ?string $slug = 'financeiro/pro-labore';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public ?array $simulationResults = null;

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->toDateString(),
            'data_fim' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Section::make('Período de Apuração')
                    ->description('Selecione o período para calcular o lucro líquido.')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('data_inicio')
                                ->label('Data Início')
                                ->required(),
                            DatePicker::make('data_fim')
                                ->label('Data Fim')
                                ->required(),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function simular(): void
    {
        $data = $this->form->getState();
        $inicio = \Carbon\Carbon::parse($data['data_inicio']);
        $fim = \Carbon\Carbon::parse($data['data_fim']);

        $calculator = new ProLaboreCalculator();

        // 1. Calcular Lucro
        $lucroLiquido = $calculator->calcularLucroLiquido($inicio, $fim);

        // 2. Calcular Reserva
        $reserva = $calculator->calcularReserva($lucroLiquido);
        $lucroDisponivel = $lucroLiquido - $reserva;

        // 3. Distribuição
        $distribuicao = $calculator->calcularDistribuicao($lucroDisponivel);

        $this->simulationResults = [
            'periodo' => $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y'),
            'lucro_liquido' => $lucroLiquido,
            'reserva' => $reserva,
            'lucro_disponivel' => $lucroDisponivel,
            'distribuicao' => $distribuicao,
            'percentual_reserva' => settings()->get('prolabore_percentual_reserva', 20),
        ];

        Notification::make()
            ->title('Simulação Realizada')
            ->success()
            ->send();
    }

    public function processar(): void
    {
        if (empty($this->simulationResults)) {
            Notification::make()->title('Simule primeiro!')->warning()->send();
            return;
        }

        $distribuicao = $this->simulationResults['distribuicao'];
        $periodo = $this->simulationResults['periodo'];

        foreach ($distribuicao as $socio) {
            if ($socio['valor'] <= 0)
                continue;

            Financeiro::create([
                'tipo' => 'saida',
                'categoria_id' => \App\Models\Categoria::where('slug', 'pro-labore')->value('id'), // Fallback handled?
                'descricao' => "Pró-Labore - {$socio['nome']} ({$periodo})",
                'valor' => $socio['valor'],
                'valor_pago' => $socio['valor'], // Já lança como pago? Decisão de negócio... Vamos deixar como pendente para confirmação no fluxo normal ou pago?
                // Vamos lançar como PENDENTE para o financeiro efetuar o pagamento real via PIX/TED
                'status' => 'pendente',
                'data_vencimento' => now()->addDays(5), // Sugestão
                'data' => now(),
                'observacoes' => "Distribuição de lucros referente ao período {$periodo}. Percentual: {$socio['percentual']}%",
                'extra_attributes' => ['user_id' => $socio['user_id'], 'origem' => 'pro_labore_automatico'],
            ]);
        }

        Notification::make()
            ->title('Distribuição Processada!')
            ->body('Os registros financeiros foram criados com status PENDENTE.')
            ->success()
            ->send();

        $this->simulationResults = null; // Clear
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('simular')
                ->label('Simular Apuração')
                ->icon('heroicon-o-calculator')
                ->action(fn() => $this->simular()),
        ];
    }
}
