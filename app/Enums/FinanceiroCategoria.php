<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinanceiroCategoria: string implements HasColor, HasLabel
{
    case Servico = 'servico';
    case Produto = 'produto';
    case Comissao = 'comissao';
    case Despesa = 'despesa';
    case Outro = 'outro';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Servico => 'Serviço',
            self::Produto => 'Produto',
            self::Comissao => 'Comissão',
            self::Despesa => 'Despesa',
            self::Outro => 'Outro',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Servico => 'success',
            self::Produto => 'info',
            self::Comissao => 'warning',
            self::Despesa => 'danger',
            self::Outro => 'gray',
        };
    }
}
