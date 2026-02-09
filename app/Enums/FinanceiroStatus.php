<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinanceiroStatus: string implements HasColor, HasLabel
{
    case Pendente = 'pendente';
    case Pago = 'pago';
    case Vencido = 'vencido';
    case Cancelado = 'cancelado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
            self::Vencido => 'Vencido',
            self::Cancelado => 'Cancelado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Pago => 'success',
            self::Vencido => 'danger',
            self::Cancelado => 'gray',
        };
    }
}
