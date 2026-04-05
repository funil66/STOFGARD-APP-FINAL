<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use App\Models\Orcamento;
use App\Models\Configuracao;
use Illuminate\Support\Collection;

class OrcamentosKanbanBoard extends KanbanBoard
{
    use \App\Traits\RestrictsAccessByTier;

    protected static ?string $title = 'Kanban de Orçamentos';

    protected static string $model = Orcamento::class;

    // We use the statuses() method dynamically.

    protected static string $recordTitleAttribute = 'numero';
    protected static string $recordStatusAttribute = 'status';

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Comercial';
    protected static ?int $navigationSort = 1;

    // Apenas visível para planos PRO e ELITE (via Trait)

    protected function statuses(): Collection
    {
        $configStatuses = Configuracao::getStatusOrcamentoOptions();

        $statuses = collect();
        foreach ($configStatuses as $id => $title) {
            $statuses->push([
                'id' => $id,
                'title' => $title,
            ]);
        }

        return $statuses;
    }

    protected function records(): Collection
    {
        return Orcamento::with('cliente')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $orcamento = Orcamento::find($recordId);
        if ($orcamento) {
            $orcamento->update(['status' => $status]);
        }
    }

    public function onSortChanged(int|string $recordId, string $status, array $orderedIds): void
    {
        // Custom sorting is disabled by default
    }
}
