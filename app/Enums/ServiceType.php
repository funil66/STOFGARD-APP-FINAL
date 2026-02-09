<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasColor, HasLabel
{
    case Higienizacao = 'higienizacao';
    case Impermeabilizacao = 'impermeabilizacao';
    case Combo = 'combo';
    case Outro = 'outro';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Higienizacao => 'Higienização',
            self::Impermeabilizacao => 'Impermeabilização',
            self::Combo => 'Combo (Higi + Imper)',
            self::Outro => 'Outro/Personalizado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Higienizacao => 'info',
            self::Impermeabilizacao => 'warning',
            self::Combo => 'success',
            self::Outro => 'gray',
        };
    }

    public function getHexColor(): string
    {
        return match ($this) {
            self::Higienizacao => '#3b82f6',
            self::Impermeabilizacao => '#f59e0b',
            self::Combo => '#10b981',
            self::Outro => '#6b7280',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Higienizacao => 'heroicon-o-sparkles',
            self::Impermeabilizacao => 'heroicon-o-shield-check',
            self::Combo => 'heroicon-o-squares-plus',
            self::Outro => 'heroicon-o-cog',
        };
    }

    public function getShortLabel(): string
    {
        return match ($this) {
            self::Higienizacao => 'HIGI',
            self::Impermeabilizacao => 'IMPER',
            self::Combo => 'COMBO',
            self::Outro => 'OUTRO',
        };
    }

    public function getDescricaoPdf(): ?string
    {
        return null;
    }
}
