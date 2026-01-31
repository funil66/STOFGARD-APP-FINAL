<?php

namespace App\Filament\Pages;

use App\Models\Orcamento; // Importando o Model do Orçamento
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class FunilVendas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string $view = 'filament.pages.funil-vendas';

    protected static ?string $title = 'Funil de Vendas';

    protected static ?string $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 1;

    // Definição das colunas do Kanban
    public array $statuses = [
        'novo' => [
            'title' => 'Novo Lead',
            'color' => 'bg-gray-100 border-gray-200',
            'icon' => 'heroicon-o-star',
        ],
        'contato_realizado' => [
            'title' => 'Contato Feito',
            'color' => 'bg-blue-50 border-blue-200',
            'icon' => 'heroicon-o-chat-bubble-left-right',
        ],
        'agendado' => [
            'title' => 'Visita Agendada',
            'color' => 'bg-yellow-50 border-yellow-200',
            'icon' => 'heroicon-o-calendar',
        ],
        'proposta_enviada' => [
            'title' => 'Proposta Enviada',
            'color' => 'bg-purple-50 border-purple-200',
            'icon' => 'heroicon-o-paper-airplane',
        ],
        'em_negociacao' => [
            'title' => 'Em Negociação',
            'color' => 'bg-orange-50 border-orange-200',
            'icon' => 'heroicon-o-currency-dollar',
        ],
        'aprovado' => [
            'title' => 'Fechado / Ganho',
            'color' => 'bg-green-50 border-green-200',
            'icon' => 'heroicon-o-check-badge',
        ],
    ];

    public function getViewData(): array
    {
        // Busca orçamentos agrupados pela etapa do funil
        // Filtra orçamentos que não estão recusados, expirados ou convertidos (exceto se etapa for aprovado)
        $orcamentos = Orcamento::query()
            ->with(['cliente'])
            ->whereNull('deleted_at')
            // Opcional: filtrar por data recente para não carregar tudo
            ->where('created_at', '>=', now()->subMonths(6))
            ->orderBy('updated_at', 'desc')
            ->get();

        return [
            'orcamentos' => $orcamentos,
            'statuses' => $this->statuses,
        ];
    }

    public function updateStatus($recordId, $status)
    {
        $orcamento = Orcamento::find($recordId);

        if ($orcamento) {
            $orcamento->update(['etapa_funil' => $status]);

            // Se moveu para aprovado, atualiza status principal também
            if ($status === 'aprovado') {
                $orcamento->update(['status' => 'aprovado']);
            }

            Notification::make()
                ->title('Etapa Atualizada')
                ->success()
                ->send();

            // Recarrega a página para refletir mudanças
            $this->redirect(static::getUrl());
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check_stalled')
                ->label('Analisar Leads Parados')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('leads:alert-stalled');

                    Notification::make()
                        ->title('Análise Concluída')
                        ->body('Se houver leads parados, você receberá notificações.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
