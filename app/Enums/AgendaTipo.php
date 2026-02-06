<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum AgendaTipo: string implements HasLabel, HasColor
{
    case Servico = 'servico';
    case Reuniao = 'reuniao';
    case Visita = 'visita';
    case Outro = 'outro';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Servico => 'Serviço',
            self::Reuniao => 'Reunião',
            self::Visita => 'Visita',
            self::Outro => 'Outro',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Servico => 'success',
            self::Reuniao => 'info',
            self::Visita => 'warning',
            self::Outro => 'gray',
        };
    }
}
