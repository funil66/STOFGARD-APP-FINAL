<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinanceiroTipo: string implements HasColor, HasLabel
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
