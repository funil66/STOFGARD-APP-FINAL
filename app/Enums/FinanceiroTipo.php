<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum FinanceiroTipo: string implements HasLabel, HasColor
{
    case Entrada = 'entrada';
    case Saida = 'saida';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida => 'Saída',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Saida => 'danger',
        };
    }
}
