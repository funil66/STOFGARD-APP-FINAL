<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AgendaStatus: string implements HasColor, HasLabel
{
    case Agendado = 'agendado';
    case EmAndamento = 'em_andamento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Agendado => 'Agendado',
            self::EmAndamento => 'Em Andamento',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Agendado => 'warning',
            self::EmAndamento => 'info',
            self::Concluido => 'success',
            self::Cancelado => 'danger',
        };
    }
}
