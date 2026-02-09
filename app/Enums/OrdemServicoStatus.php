<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrdemServicoStatus: string implements HasColor, HasLabel
{
    case Aberta = 'aberta';
    case EmExecucao = 'em_execucao';
    case Concluida = 'concluido';
    case Cancelada = 'cancelada';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::EmExecucao => 'Em Execução',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Aberta => 'warning',
            self::EmExecucao => 'info',
            self::Concluida => 'success',
            self::Cancelada => 'danger',
        };
    }
}
